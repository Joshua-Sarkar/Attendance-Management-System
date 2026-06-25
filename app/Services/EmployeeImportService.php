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
        $defaultPassword = env('DEFAULT_EMPLOYEE_PASSWORD');
        if (empty($defaultPassword)) {
            throw new \Exception("The DEFAULT_EMPLOYEE_PASSWORD environment variable is not configured. Please set it before importing.");
        }

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
        $headerCounts = [];
        foreach ($headerRow as $columnLetter => $headerName) {
            if ($headerName !== null) {
                $name = trim($headerName);
                if (!isset($headerCounts[$name])) {
                    $headerCounts[$name] = 0;
                }
                $headerCounts[$name]++;
                
                $key = $name;
                if ($headerCounts[$name] > 1) {
                    $key = $name . '.' . ($headerCounts[$name] - 1);
                }
                $headersMap[$key] = $columnLetter;
            }
        }

        // Validate essential headers
        $essentialHeaders = ['Employee Code', 'Official Email ID', 'Full Name'];
        $hasEssential = true;
        foreach ($essentialHeaders as $header) {
            if ($this->resolveHeader($headersMap, [$header]) === null) {
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

                $employeeCode = $this->getVal($row, $headersMap, ['Employee Code', 'Employee ID', 'Code']);
                $officialEmail = $this->getVal($row, $headersMap, ['Official Email ID', 'Official Email', 'Email ID']);

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
                $deptName = $this->getVal($row, $headersMap, ['Department', 'Dept']);
                $department = null;
                if (!empty($deptName)) {
                    $deptNameTrimmed = trim($deptName);
                    $department = Department::whereRaw('LOWER(name) = ?', [strtolower($deptNameTrimmed)])->first();
                }

                if (!$department) {
                    $errors[] = [
                        'row' => $rowIndex,
                        'reason' => "Department not found or could not be created for '" . ($deptName ?? 'empty') . "'.",
                    ];
                    continue;
                }

                // Resolve status
                $statusVal = $this->getVal($row, $headersMap, ['Employee Status', 'Status']);
                $mappedStatus = null;
                if (!empty($statusVal)) {
                    $trimmedStatus = strtolower(trim($statusVal));
                    if (in_array($trimmedStatus, ['active', 'probation', 'confirmed'])) {
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

                $fullName = trim($this->getVal($row, $headersMap, ['Full Name', 'Employee Name', 'Name']));
                if (empty($fullName)) {
                    $errors[] = [
                        'row' => $rowIndex,
                        'reason' => "Missing Full Name.",
                    ];
                    continue;
                }

                $mobileNo = $this->cleanPhoneNumber($this->getVal($row, $headersMap, ['Mobile No.', 'Mobile', 'Mobile Number']));
                $joiningDate = $this->parseExcelDate($this->getVal($row, $headersMap, ['Joining Date', 'Date of Joining']));

                if (!$user) {
                    // Create mode
                    if (empty($officialEmail)) {
                        $errors[] = [
                            'row' => $rowIndex,
                            'reason' => "Cannot create user without Official Email ID.",
                        ];
                        continue;
                    }

                    $user = new User();
                    $user->employee_id = $standardizedId;
                    $user->name = $fullName;
                    $user->email = $officialEmail;
                    $user->role = 'employee';
                    $user->status = $mappedStatus;
                    $user->phone = $mobileNo;
                    $user->joining_date = $joiningDate;
                    $user->department_id = $department->id;
                    $user->must_change_password = true;
                    $user->password = \Illuminate\Support\Facades\Hash::make($defaultPassword);
                    $user->save();

                    \App\Services\LeaveBalanceService::initializeUser($user);

                    $usersCreatedCount++;
                } else {
                    // Update mode - ONLY update status, department_id, and phone/joining_date if needed
                    $user->status = $mappedStatus;
                    $user->department_id = $department->id;
                    if ($mobileNo) {
                        $user->phone = $mobileNo;
                    }
                    if ($joiningDate) {
                        $user->joining_date = $joiningDate;
                    }
                    $user->save();

                    $usersUpdatedCount++;
                }

                // Gather profile data
                $profileData = [
                    'father_name' => $this->getVal($row, $headersMap, ['Father Name', 'Father\'s Name']),
                    'mother_name' => $this->getVal($row, $headersMap, ['Mother Name', 'Mother\'s Name']),
                    'gender' => $this->getVal($row, $headersMap, ['Gender', 'Sex']),
                    'date_of_birth' => $this->parseExcelDate($this->getVal($row, $headersMap, ['Date of birth', 'DOB', 'Birth Date'])),
                    'marital_status' => $this->getVal($row, $headersMap, ['Marital Status']),
                    'date_of_marriage' => $this->parseExcelDate($this->getVal($row, $headersMap, ['Date of Marriage', 'DOM'])),
                    'nationality' => $this->getVal($row, $headersMap, ['Nationality']),
                    'blood_group' => $this->getVal($row, $headersMap, ['Blood Group', 'Blood Grouping']),
                    'personal_email' => $this->getVal($row, $headersMap, ['Personal Email ID', 'Personal Email']),
                    'mobile_no' => $mobileNo,
                    'pf_uan' => $this->getVal($row, $headersMap, ['PF UAN', 'UAN']),
                    'passport_no' => $this->getVal($row, $headersMap, ['Passport No.', 'Passport Number', 'Passport']),
                    'aadhar_card' => $this->getVal($row, $headersMap, ['Aadhar Card', 'Aadhar No', 'Aadhar']),
                    'pan' => $this->getVal($row, $headersMap, ['PAN', 'PAN Card', 'PAN No']),
                    'pf_no' => $this->getVal($row, $headersMap, ['PF NO', 'PF Number']),
                    'esi_number' => $this->getVal($row, $headersMap, ['Esi number', 'ESI No', 'ESI']),
                    'date_of_gratuity' => $this->parseExcelDate($this->getVal($row, $headersMap, ['Date of Gratuity'])),
                    'payroll_type' => $this->getVal($row, $headersMap, ['Payroll Type']),
                    'contract_end_date' => $this->parseExcelDate($this->getVal($row, $headersMap, ['Contract End Date'])),
                    'office_landline' => $this->cleanPhoneNumber($this->getVal($row, $headersMap, ['Office Landline Number', 'Office Landline'])),
                    'leave_rule' => $this->getVal($row, $headersMap, ['Leave Rule']),
                    'shift' => $this->getVal($row, $headersMap, ['Shift']),
                    'designation' => $this->getVal($row, $headersMap, ['Designation']),
                    'grade' => $this->getVal($row, $headersMap, ['Grade']),
                    'employee_type' => $this->getVal($row, $headersMap, ['Employee Type']),
                    'company' => $this->getVal($row, $headersMap, ['Company']),
                    'location' => $this->getVal($row, $headersMap, ['Location']),
                    'biometric_id' => $this->getVal($row, $headersMap, ['Biometric Id', 'Biometric ID']),
                    'hiring_source' => $this->getVal($row, $headersMap, ['Hiring source', 'Hiring Source']),
                    'source_of_verification' => $this->getVal($row, $headersMap, ['Source of verification']),
                    
                    // Address fields
                    'current_address1' => $this->getVal($row, $headersMap, ['Current Address1', 'Address1']),
                    'current_address2' => $this->getVal($row, $headersMap, ['Current Address2', 'Address2']),
                    'current_country' => $this->getVal($row, $headersMap, ['Country'], 1),
                    'current_state' => $this->getVal($row, $headersMap, ['State'], 1),
                    'current_city' => $this->getVal($row, $headersMap, ['City'], 1),
                    'current_zip' => $this->getVal($row, $headersMap, ['Zip'], 1),
                    
                    'permanent_address1' => $this->getVal($row, $headersMap, ['Permanent Address1']),
                    'permanent_address2' => $this->getVal($row, $headersMap, ['Permanent Address2']),
                    'permanent_country' => $this->getVal($row, $headersMap, ['Country'], 2),
                    'permanent_state' => $this->getVal($row, $headersMap, ['State'], 2),
                    'permanent_city' => $this->getVal($row, $headersMap, ['City'], 2),
                    'permanent_zip' => $this->getVal($row, $headersMap, ['Zip'], 2),
                    
                    'same_as_current_address' => $this->parseBoolean($this->getVal($row, $headersMap, ['Same as current address'])),
                    'payment_type' => $this->getVal($row, $headersMap, ['Payment Type']),
                    'bank_name' => $this->getVal($row, $headersMap, ['Bank Name']),
                    'account_holder_name' => $this->getVal($row, $headersMap, ['Account Holder Name']),
                    'account_no' => $this->getVal($row, $headersMap, ['Account No', 'Account Number']),
                    'ifsc_code' => $this->getVal($row, $headersMap, ['IFSC code', 'IFSC']),
                    
                    // Emergency contacts
                    'emergency_name' => $this->getVal($row, $headersMap, ['Emergency Contact Name', 'Emergency Name', 'Name'], 2),
                    'emergency_relationship' => $this->getVal($row, $headersMap, ['Emergency Relationship', 'Relationship'], 2),
                    'emergency_address' => $this->getVal($row, $headersMap, ['Emergency Contact Address', 'Emergency Address', 'Address'], 2),
                    'emergency_email' => $this->getVal($row, $headersMap, ['Emergency Contact Email', 'Emergency Email', 'Email'], 2),
                    'emergency_mobile' => $this->cleanPhoneNumber($this->getVal($row, $headersMap, ['Emergency Contact Mobile', 'Emergency Mobile', 'Mobile No.'], 2)),
                    
                    // Education & Experience
                    'degree_name' => $this->getVal($row, $headersMap, ['Diploma/Degree Name', 'Degree Name']),
                    'institution_name' => $this->getVal($row, $headersMap, ['Institution Name', 'College/School Name']),
                    'passing_year' => $this->getVal($row, $headersMap, ['Passing Year']),
                    'percentage' => $this->getVal($row, $headersMap, ['Percentage', 'Marks %']),
                    'previous_company_name' => $this->getVal($row, $headersMap, ['Previous Company Name', 'Previous Company']),
                    'previous_job_title' => $this->getVal($row, $headersMap, ['Job Title', 'Designation'], 2),
                    'previous_from_date' => $this->parseExcelDate($this->getVal($row, $headersMap, ['From Date'])),
                    'previous_to_date' => $this->parseExcelDate($this->getVal($row, $headersMap, ['To Date'])),
                    'state_name' => $this->getVal($row, $headersMap, ['STATE NAME']),
                    'probation_period' => $this->getVal($row, $headersMap, ['PROBATION PERIOD']),
                    'probation_confirm_date' => $this->parseExcelDate($this->getVal($row, $headersMap, ['PROBATION CONFIRM_DATE'])),
                    'separation_date' => $this->parseExcelDate($this->getVal($row, $headersMap, ['SEPRATE DATE'])),
                    'last_working_day' => $this->parseExcelDate($this->getVal($row, $headersMap, ['LWD', 'Last Working Day'])),
                    'previous_year_experience' => $this->trimVal($this->getVal($row, $headersMap, ['PREVIOUS YEAR_EXPERIENCE'])),
                    'years_completed' => $this->trimVal($this->getVal($row, $headersMap, ['NUMBER OF_YEAR_COMPLETED'])),
                    'overall_year_experience' => $this->trimVal($this->getVal($row, $headersMap, ['OVERALL YEAR_EXPERIENCE'])),
                    'city_type' => $this->getVal($row, $headersMap, ['City Type']),
                    'notice_days' => $this->parseInteger($this->getVal($row, $headersMap, ['Notice Days'])),
                    'joining_date' => $joiningDate,
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
                $managerCol = trim($this->getVal($row, $headersMap, ['Reporting Manager', 'Manager']) ?? '');

                if (!empty($managerCol)) {
                    $resolvedManagerId = null;

                    // 1. Try parentheses match, e.g. "Name (EMP00001)" or "Name (1)"
                    if (preg_match('/\(([^)]+)\)/', $managerCol, $matches)) {
                        $extractedCode = trim($matches[1]);
                        
                        // Try matching standardized employee code (e.g. EMP00001)
                        $stdCode = $this->standardizeEmployeeId($extractedCode);
                        $managerUser = User::where('employee_id', $stdCode)
                            ->orWhere('employee_id', $extractedCode)
                            ->first();
                        
                        if ($managerUser) {
                            $resolvedManagerId = $managerUser->id;
                        } else {
                            // Try numeric ID match in $userLookup
                            $managerCodeInt = (int) $extractedCode;
                            if (isset($userLookup[$managerCodeInt])) {
                                $resolvedManagerId = $userLookup[$managerCodeInt];
                            }
                        }
                    }

                    // 2. Try raw employee code directly, e.g. "EMP00001" or "1"
                    if (!$resolvedManagerId) {
                        $stdCode = $this->standardizeEmployeeId($managerCol);
                        $managerUser = User::where('employee_id', $stdCode)
                            ->orWhere('employee_id', $managerCol)
                            ->first();
                        if ($managerUser) {
                            $resolvedManagerId = $managerUser->id;
                        }
                    }

                    // 3. Fallback: match by full name (case insensitive trim check)
                    if (!$resolvedManagerId) {
                        $managerUser = User::whereRaw('LOWER(name) = ?', [strtolower(trim($managerCol))])->first();
                        if ($managerUser) {
                            $resolvedManagerId = $managerUser->id;
                        }
                    }

                    // 4. Create manager on-the-fly if not found
                    if (!$resolvedManagerId) {
                        $mgrName = $managerCol;
                        $mgrCode = null;
                        if (preg_match('/^([^(]+)\(([^)]+)\)/', $managerCol, $parts)) {
                            $mgrName = trim($parts[1]);
                            $mgrCode = $this->standardizeEmployeeId(trim($parts[2]));
                        } else {
                            $mgrCode = $this->standardizeEmployeeId($managerCol);
                        }

                        if ($mgrCode && !str_starts_with($mgrCode, 'EMP')) {
                            $mgrCode = null;
                        }

                        if (!$mgrCode) {
                            $latestUser = User::where('employee_id', 'like', 'EMP%')->orderBy('employee_id', 'desc')->first();
                            $num = 1000;
                            if ($latestUser && preg_match('/\d+/', $latestUser->employee_id, $numMatches)) {
                                $num = (int)$numMatches[0] + 1;
                            }
                            $mgrCode = 'EMP' . str_pad($num, 5, '0', STR_PAD_LEFT);
                        }

                        $emailLocal = strtolower(preg_replace('/[^A-Za-z0-9]/', '.', $mgrName));
                        $email = $emailLocal . '@ams.com';
                        $existsEmail = User::where('email', $email)->first();
                        if ($existsEmail) {
                            $email = $emailLocal . rand(100, 999) . '@ams.com';
                        }

                        $managerUser = User::create([
                            'employee_id' => $mgrCode,
                            'name' => $mgrName,
                            'email' => $email,
                            'role' => 'manager',
                            'status' => 'active',
                            'must_change_password' => true,
                            'password' => \Illuminate\Support\Facades\Hash::make($defaultPassword),
                        ]);

                        \App\Services\LeaveBalanceService::initializeUser($managerUser);

                        EmployeeProfile::create([
                            'user_id' => $managerUser->id,
                        ]);

                        $resolvedManagerId = $managerUser->id;
                    }

                    if ($resolvedManagerId) {
                        $user->manager_id = $resolvedManagerId;
                        $user->save();

                        // Automatically promote resolved manager to 'manager' role
                        $mgrUser = User::find($resolvedManagerId);
                        if ($mgrUser && $mgrUser->role === 'employee') {
                            $mgrUser->role = 'manager';
                            $mgrUser->save();
                        }
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

    private function getVal(array $row, array $headersMap, string|array $headerNames, int $occurrence = 1)
    {
        $candidates = is_array($headerNames) ? $headerNames : [$headerNames];
        $resolvedKey = $this->resolveHeader($headersMap, $candidates, $occurrence);
        if ($resolvedKey !== null && isset($headersMap[$resolvedKey])) {
            $colLetter = $headersMap[$resolvedKey];
            return $row[$colLetter] ?? null;
        }
        return null;
    }

    private function resolveHeader(array $headersMap, array $candidates, int $occurrence = 1): ?string
    {
        foreach ($candidates as $candidate) {
            if ($occurrence === 1) {
                if (isset($headersMap[$candidate])) {
                    return $candidate;
                }
            } else {
                $key = $candidate . '.' . ($occurrence - 1);
                if (isset($headersMap[$key])) {
                    return $key;
                }
            }
        }
        
        // Fallback for occurrence > 1 if no suffix match was found
        if ($occurrence > 1) {
            foreach ($candidates as $candidate) {
                if (isset($headersMap[$candidate])) {
                    return $candidate;
                }
            }
        }
        return null;
    }

    private function cleanPhoneNumber($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $value = trim((string) $value);
        if (str_ends_with($value, '.0')) {
            $value = substr($value, 0, -2);
        }
        $cleaned = preg_replace('/[^\d+]/', '', $value);
        return $cleaned !== '' ? $cleaned : null;
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
