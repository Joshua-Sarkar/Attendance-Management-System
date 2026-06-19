<?php

namespace App\Services;

use App\Models\User;
use App\Models\EmployeeProfile;
use App\Models\Department;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

class EmployeeImportService
{
    /**
     * Import employees and profiles from a file.
     *
     * @param string $filePath
     * @return array
     * @throws \Exception
     */
    public function import(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \Exception("File not found: {$filePath}");
        }

        try {
            $spreadsheet = IOFactory::load($filePath);
        } catch (\Exception $e) {
            throw new \Exception("Failed to load Excel/CSV file: " . $e->getMessage(), 0, $e);
        }

        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $headerRow = $rows[1] ?? null;
        if (!$headerRow) {
            throw new \Exception("Excel file is empty or missing headers.");
        }

        $headersMap = [];
        foreach ($headerRow as $columnLetter => $headerName) {
            if ($headerName !== null) {
                $headersMap[trim($headerName)] = $columnLetter;
            }
        }

        // Validate essential headers
        $essentialHeaders = ['Employee Code', 'Official Email ID', 'Full Name'];
        foreach ($essentialHeaders as $header) {
            if (!isset($headersMap[$header])) {
                throw new \Exception("Missing essential header: '{$header}'");
            }
        }

        $rowsProcessed = 0;
        $usersCreatedCount = 0;
        $usersUpdatedCount = 0;
        $errors = [];
        $processedUsers = []; // rowIndex => User

        DB::beginTransaction();

        try {
            // PASS 1: Create or Update User & Profile (excluding manager_id)
            foreach ($rows as $rowIndex => $row) {
                if ($rowIndex === 1) {
                    continue; // Skip header row
                }

                // Check if row is completely empty
                $nonEmptyCells = array_filter($row, fn($val) => $val !== null && trim($val) !== '');
                if (empty($nonEmptyCells)) {
                    continue;
                }

                $rowsProcessed++;

                $employeeCode = $this->getVal($row, $headersMap, 'Employee Code');
                $officialEmail = $this->getVal($row, $headersMap, 'Official Email ID');

                $standardizedId = $this->standardizeEmployeeId($employeeCode);
                $officialEmail = trim($officialEmail);

                if (empty($standardizedId) && empty($officialEmail)) {
                    $errors[] = [
                        'row' => $rowIndex,
                        'reason' => "Row skipped: both Employee Code and Official Email ID are empty.",
                    ];
                    continue;
                }

                // Match existing user
                $user = null;
                if (!empty($standardizedId)) {
                    $user = User::where('employee_id', $standardizedId)->first();
                }
                if (!$user && !empty($officialEmail)) {
                    $user = User::where('email', $officialEmail)->first();
                }

                // Resolve department_id
                $deptName = $this->getVal($row, $headersMap, 'Department');
                $department = null;
                if (!empty($deptName)) {
                    $department = Department::whereRaw('LOWER(name) = ?', [strtolower(trim($deptName))])->first();
                }

                if (!$department) {
                    $errors[] = [
                        'row' => $rowIndex,
                        'reason' => "Department not found: '" . ($deptName ?? 'empty') . "'.",
                    ];
                    continue;
                }

                // Resolve status
                $statusVal = $this->getVal($row, $headersMap, 'Employee Status');
                $mappedStatus = null;
                if (!empty($statusVal)) {
                    $trimmedStatus = strtolower(trim($statusVal));
                    if (in_array($trimmedStatus, ['active', 'probation'])) {
                        $mappedStatus = 'active';
                    } else {
                        $errors[] = [
                            'row' => $rowIndex,
                            'reason' => "Invalid employee status: '{$statusVal}'.",
                        ];
                        continue;
                    }
                } else {
                    $errors[] = [
                        'row' => $rowIndex,
                        'reason' => "Missing employee status.",
                    ];
                    continue;
                }

                $fullName = trim($this->getVal($row, $headersMap, 'Full Name'));
                if (empty($fullName)) {
                    $errors[] = [
                        'row' => $rowIndex,
                        'reason' => "Missing Full Name.",
                    ];
                    continue;
                }

                $mobileNo = $this->getVal($row, $headersMap, 'Mobile No.');

                if (!$user) {
                    // Create mode
                    if (empty($officialEmail)) {
                        $errors[] = [
                            'row' => $rowIndex,
                            'reason' => "Cannot create user without Official Email ID.",
                        ];
                        continue;
                    }

                    $tempPassword = Str::random(10);
                    $user = new User();
                    $user->employee_id = $standardizedId;
                    $user->name = $fullName;
                    $user->email = $officialEmail;
                    $user->role = 'employee';
                    $user->status = $mappedStatus;
                    $user->phone = $mobileNo;
                    $user->joining_date = $this->parseExcelDate($this->getVal($row, $headersMap, 'Joining Date'));
                    $user->department_id = $department->id;
                    $user->must_change_password = true;
                    $user->password = bcrypt($tempPassword);
                    $user->save();

                    $usersCreatedCount++;
                } else {
                    // Update mode - ONLY update status, department_id, and phone/joining_date if needed
                    $user->status = $mappedStatus;
                    $user->department_id = $department->id;
                    if ($mobileNo) {
                        $user->phone = $mobileNo;
                    }
                    $joiningDate = $this->parseExcelDate($this->getVal($row, $headersMap, 'Joining Date'));
                    if ($joiningDate) {
                        $user->joining_date = $joiningDate;
                    }
                    $user->save();

                    $usersUpdatedCount++;
                }

                // Gather profile data
                $profileData = [
                    'father_name' => $this->getVal($row, $headersMap, 'Father Name'),
                    'mother_name' => $this->getVal($row, $headersMap, 'Mother Name'),
                    'gender' => $this->getVal($row, $headersMap, 'Gender'),
                    'date_of_birth' => $this->parseExcelDate($this->getVal($row, $headersMap, 'Date of birth')),
                    'marital_status' => $this->getVal($row, $headersMap, 'Marital Status'),
                    'date_of_marriage' => $this->parseExcelDate($this->getVal($row, $headersMap, 'Date of Marriage')),
                    'nationality' => $this->getVal($row, $headersMap, 'Nationality'),
                    'blood_group' => $this->getVal($row, $headersMap, 'Blood Group'),
                    'personal_email' => $this->getVal($row, $headersMap, 'Personal Email ID'),
                    'mobile_no' => $mobileNo,
                    'pf_uan' => $this->getVal($row, $headersMap, 'PF UAN'),
                    'passport_no' => $this->getVal($row, $headersMap, 'Passport No.'),
                    'aadhar_card' => $this->getVal($row, $headersMap, 'Aadhar Card'),
                    'pan' => $this->getVal($row, $headersMap, 'PAN'),
                    'pf_no' => $this->getVal($row, $headersMap, 'PF NO'),
                    'esi_number' => $this->getVal($row, $headersMap, 'Esi number'),
                    'date_of_gratuity' => $this->parseExcelDate($this->getVal($row, $headersMap, 'Date of Gratuity')),
                    'payroll_type' => $this->getVal($row, $headersMap, 'Payroll Type'),
                    'contract_end_date' => $this->parseExcelDate($this->getVal($row, $headersMap, 'Contract End Date')),
                    'office_landline' => $this->getVal($row, $headersMap, 'Office Landline Number'),
                    'leave_rule' => $this->getVal($row, $headersMap, 'Leave Rule'),
                    'shift' => $this->getVal($row, $headersMap, 'Shift'),
                    'designation' => $this->getVal($row, $headersMap, 'Designation'),
                    'grade' => $this->getVal($row, $headersMap, 'Grade'),
                    'employee_type' => $this->getVal($row, $headersMap, 'Employee Type'),
                    'company' => $this->getVal($row, $headersMap, 'Company'),
                    'location' => $this->getVal($row, $headersMap, 'Location'),
                    'biometric_id' => $this->getVal($row, $headersMap, 'Biometric Id'),
                    'hiring_source' => $this->getVal($row, $headersMap, 'Hiring source'),
                    'source_of_verification' => $this->getVal($row, $headersMap, 'Source of verification'),
                    'current_address1' => $this->getVal($row, $headersMap, 'Current Address1'),
                    'current_address2' => $this->getVal($row, $headersMap, 'Current Address2'),
                    'current_country' => $this->getVal($row, $headersMap, 'Country'),
                    'current_state' => $this->getVal($row, $headersMap, 'State'),
                    'current_city' => $this->getVal($row, $headersMap, 'City'),
                    'current_zip' => $this->getVal($row, $headersMap, 'Zip'),
                    'permanent_address1' => $this->getVal($row, $headersMap, 'Permanent Address1'),
                    'permanent_address2' => $this->getVal($row, $headersMap, 'Permanent Address2'),
                    'permanent_country' => $this->getVal($row, $headersMap, 'Country.1'),
                    'permanent_state' => $this->getVal($row, $headersMap, 'State.1'),
                    'permanent_city' => $this->getVal($row, $headersMap, 'City.1'),
                    'permanent_zip' => $this->getVal($row, $headersMap, 'Zip.1'),
                    'same_as_current_address' => $this->parseBoolean($this->getVal($row, $headersMap, 'Same as current address')),
                    'payment_type' => $this->getVal($row, $headersMap, 'Payment Type'),
                    'bank_name' => $this->getVal($row, $headersMap, 'Bank Name'),
                    'account_holder_name' => $this->getVal($row, $headersMap, 'Account Holder Name'),
                    'account_no' => $this->getVal($row, $headersMap, 'Account No'),
                    'ifsc_code' => $this->getVal($row, $headersMap, 'IFSC code'),
                    'emergency_name' => $this->getVal($row, $headersMap, 'Name'),
                    'emergency_relationship' => $this->getVal($row, $headersMap, 'Relationship'),
                    'emergency_address' => $this->getVal($row, $headersMap, 'Address'),
                    'emergency_email' => $this->getVal($row, $headersMap, 'Email'),
                    'emergency_mobile' => $this->getVal($row, $headersMap, 'Mobile No..1'),
                    'degree_name' => $this->getVal($row, $headersMap, 'Diploma/Degree Name'),
                    'institution_name' => $this->getVal($row, $headersMap, 'Institution Name'),
                    'passing_year' => $this->getVal($row, $headersMap, 'Passing Year'),
                    'percentage' => $this->getVal($row, $headersMap, 'Percentage'),
                    'previous_company_name' => $this->getVal($row, $headersMap, 'Previous Company Name'),
                    'previous_job_title' => $this->getVal($row, $headersMap, 'Job Title'),
                    'previous_from_date' => $this->parseExcelDate($this->getVal($row, $headersMap, 'From Date')),
                    'previous_to_date' => $this->parseExcelDate($this->getVal($row, $headersMap, 'To Date')),
                    'state_name' => $this->getVal($row, $headersMap, 'STATE NAME'),
                    'probation_period' => $this->getVal($row, $headersMap, 'PROBATION PERIOD'),
                    'probation_confirm_date' => $this->parseExcelDate($this->getVal($row, $headersMap, 'PROBATION CONFIRM_DATE')),
                    'separation_date' => $this->parseExcelDate($this->getVal($row, $headersMap, 'SEPRATE DATE')),
                    'last_working_day' => $this->parseExcelDate($this->getVal($row, $headersMap, 'LWD')),
                    'previous_year_experience' => $this->trimVal($this->getVal($row, $headersMap, 'PREVIOUS YEAR_EXPERIENCE')),
                    'years_completed' => $this->trimVal($this->getVal($row, $headersMap, 'NUMBER OF_YEAR_COMPLETED')),
                    'overall_year_experience' => $this->trimVal($this->getVal($row, $headersMap, 'OVERALL YEAR_EXPERIENCE')),
                    'city_type' => $this->getVal($row, $headersMap, 'City Type'),
                    'notice_days' => $this->parseInteger($this->getVal($row, $headersMap, 'Notice Days')),
                    'joining_date' => $this->parseExcelDate($this->getVal($row, $headersMap, 'Joining Date')),
                ];

                EmployeeProfile::updateOrCreate(
                    ['user_id' => $user->id],
                    $profileData
                );

                $processedUsers[$rowIndex] = $user;
            }

            // Build in-memory lookup map of employee_id numeric values to user IDs
            $allUsers = User::select('id', 'employee_id')->whereNotNull('employee_id')->get();
            $userLookup = [];
            foreach ($allUsers as $u) {
                if (preg_match('/\d+/', $u->employee_id, $idMatches)) {
                    $numericId = (int) $idMatches[0];
                    $userLookup[$numericId] = $u->id;
                }
            }

            // PASS 2: Update manager_id for successfully processed users
            foreach ($processedUsers as $rowIndex => $user) {
                $row = $rows[$rowIndex];
                $managerCol = $this->getVal($row, $headersMap, 'Reporting Manager');

                if (!empty($managerCol)) {
                    if (preg_match('/\(([^)]+)\)/', $managerCol, $matches)) {
                        $extractedCode = trim($matches[1]);
                        $managerCodeInt = (int) $extractedCode;

                        if (isset($userLookup[$managerCodeInt])) {
                            $user->manager_id = $userLookup[$managerCodeInt];
                            $user->save();
                        } else {
                            $errors[] = [
                                'row' => $rowIndex,
                                'reason' => "Reporting manager with code '{$extractedCode}' (numeric: {$managerCodeInt}) not found in database.",
                            ];
                        }
                    } else {
                        $errors[] = [
                            'row' => $rowIndex,
                            'reason' => "Invalid format for Reporting Manager: '{$managerCol}'. Could not parse code in parentheses.",
                        ];
                    }
                } else {
                    $user->manager_id = null;
                    $user->save();
                }
            }

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'rows_processed' => $rowsProcessed,
            'created' => $usersCreatedCount,
            'updated' => $usersUpdatedCount,
            'errors' => $errors,
        ];
    }

    private function getVal(array $row, array $headersMap, string $headerName)
    {
        $colLetter = $headersMap[$headerName] ?? null;
        return $colLetter !== null ? $row[$colLetter] : null;
    }

    private function standardizeEmployeeId($code): ?string
    {
        if ($code === null || $code === '') {
            return null;
        }
        $code = trim($code);
        if (preg_match('/^EMP0*(\d+)$/i', $code, $matches)) {
            return 'EMP' . str_pad($matches[1], 5, '0', STR_PAD_LEFT);
        }
        if (is_numeric($code)) {
            return 'EMP' . str_pad($code, 5, '0', STR_PAD_LEFT);
        }
        return strtoupper($code);
    }

    private function parseExcelDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $value = trim($value);
        if (is_numeric($value)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$value)->format('Y-m-d');
            } catch (\Exception $e) {
                // fall back to string parsing
            }
        }
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parseBoolean($value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }
        $value = strtolower(trim($value));
        return in_array($value, ['yes', '1', 'true', 'y', 'same_as_current_address', 'same as current address']);
    }

    private function parseNumeric($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        $cleaned = preg_replace('/[^0-9.-]/', '', $value);
        return is_numeric($cleaned) ? (float) $cleaned : null;
    }

    private function parseInteger($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $cleaned = preg_replace('/[^0-9-]/', '', $value);
        return is_numeric($cleaned) ? (int) $cleaned : null;
    }

    private function trimVal($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        return trim($value);
    }
}
