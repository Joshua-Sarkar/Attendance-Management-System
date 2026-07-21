<?php

namespace App\Services;

use App\Models\User;
use App\Models\EmployeeProfile;
use App\Models\Department;
use App\Models\PayrollProfile;
use App\Models\SalaryHistory;
use App\Models\LeaveBalance;
use App\Models\LeaveLedgerEntry;
use App\Models\ImportProfile;
use App\Models\EmployeeExternalIdentifier;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

class EmployeeImportService
{
    public function __construct(
        public EmployeeMatchingService $matchingService
    ) {}

    /**
     * Ensure the standard default import profiles exist in the database.
     */
    public function ensureDefaultProfilesExist(): void
    {
        // Auto-refresh profiles if they are legacy or incomplete
        $standardProfile = ImportProfile::where('name', 'Standard Employee Master')->first();
        if (ImportProfile::count() < 6 || !$standardProfile || !isset($standardProfile->mappings['base_salary']) || !isset($standardProfile->matching_weights)) {
            ImportProfile::truncate();
        }

        if (ImportProfile::count() === 0) {
            $defaultWeights = [
                'employee_id' => 60,
                'email' => 50,
                'external_identifier' => 40,
                'name' => 25,
                'department' => 15,
                'designation' => 10,
                'joining_date' => 10,
                'mobile' => 5,
            ];

            // 1. Standard Employee Master
            ImportProfile::create([
                'name' => 'Standard Employee Master',
                'source_system' => 'Standard',
                'version' => '1.0',
                'is_default' => true,
                'mappings' => [
                    'employee_code' => ['employee code', 'employee id', 'emp id', 'empid', 'code', 'id'],
                    'full_name' => ['employee name', 'emp name', 'full name', 'name', 'employee_name'],
                    'official_email' => ['official email id', 'official email', 'email id', 'email', 'official_email_id'],
                    'department' => ['department', 'dept', 'department_name'],
                    'status' => ['employee status', 'status', 'employee_status'],
                    'reporting_manager' => ['reporting manager', 'manager', 'reporting_manager'],
                    'mobile_no' => ['mobile no.', 'mobile', 'mobile number', 'mobile_no'],
                    'joining_date' => ['joining date', 'date of joining', 'joining_date'],
                    'designation' => ['designation', 'job title', 'role'],
                    'base_salary' => ['base salary', 'salary', 'emp salary', 'employee salary'],
                    'salary_effective_date' => ['salary effective date', 'effective date', 'salary_effective_date'],
                    'payroll_enabled' => ['payroll enabled', 'enabled', 'payroll_enabled'],
                    'planned_leave' => ['planned leave', 'planned', 'plannedleave'],
                    'unplanned_leave' => ['unplanned leave', 'unplanned', 'unplannedleave'],
                    'paternity_leave' => ['paternity leave', 'paternity', 'paternityleave'],
                    'maternity_leave' => ['maternity leave', 'maternity', 'maternityleave'],
                    'compensatory_leave' => ['compensatory leave', 'compensatory', 'compensatoryleave'],
                    'pending_leave' => ['pending leave', 'pending', 'pendingleave'],
                    'utilized_leave' => ['utilized leave', 'utilized', 'utilizedleave'],
                    'carry_forward' => ['carry forward', 'carryforward', 'carry_forward'],
                    'remaining_leave' => ['remaining leave', 'remaining', 'remainingleave'],
                ],
                'matching_weights' => $defaultWeights,
            ]);

            // 2. Payroll Register
            ImportProfile::create([
                'name' => 'Payroll Register',
                'source_system' => 'Payroll',
                'version' => '1.0',
                'is_default' => false,
                'mappings' => [
                    'employee_code' => ['employee code', 'employee id', 'emp id', 'code', 'id'],
                    'base_salary' => ['base salary', 'salary', 'emp salary', 'employee salary'],
                    'salary_effective_date' => ['salary effective date', 'effective date', 'salary_effective_date'],
                    'payroll_enabled' => ['payroll enabled', 'enabled', 'payroll_enabled'],
                ],
                'matching_weights' => $defaultWeights,
            ]);

            // 3. Leave Balance Register
            ImportProfile::create([
                'name' => 'Leave Balance Register',
                'source_system' => 'Leave',
                'version' => '1.0',
                'is_default' => false,
                'mappings' => [
                    'employee_code' => ['employee code', 'employee id', 'emp id', 'code', 'id'],
                    'planned_leave' => ['planned leave', 'planned', 'plannedleave'],
                    'unplanned_leave' => ['unplanned leave', 'unplanned', 'unplannedleave'],
                    'paternity_leave' => ['paternity leave', 'paternity', 'paternityleave'],
                    'maternity_leave' => ['maternity leave', 'maternity', 'maternityleave'],
                    'compensatory_leave' => ['compensatory leave', 'compensatory', 'compensatoryleave'],
                    'pending_leave' => ['pending leave', 'pending', 'pendingleave'],
                    'utilized_leave' => ['utilized leave', 'utilized', 'utilizedleave'],
                    'carry_forward' => ['carry forward', 'carryforward', 'carry_forward'],
                    'remaining_leave' => ['remaining leave', 'remaining', 'remainingleave'],
                ],
                'matching_weights' => $defaultWeights,
            ]);

            // 4. Attendance Register
            ImportProfile::create([
                'name' => 'Attendance Register',
                'source_system' => 'Attendance',
                'version' => '1.0',
                'is_default' => false,
                'mappings' => [
                    'employee_code' => ['employee code', 'employee id', 'emp id', 'code', 'id'],
                    'attendance_date' => ['date', 'attendance date', 'day'],
                    'status' => ['status', 'attendance status'],
                ],
                'matching_weights' => $defaultWeights,
            ]);

            // 5. Zimyo Export
            ImportProfile::create([
                'name' => 'Zimyo Export',
                'source_system' => 'Zimyo',
                'version' => '1.0',
                'is_default' => false,
                'mappings' => [
                    'employee_code' => ['emp code', 'zimyo code', 'zm code'],
                    'full_name' => ['name', 'display name'],
                    'official_email' => ['email', 'official email'],
                ],
                'matching_weights' => $defaultWeights,
            ]);

            // 6. Custom Profile
            ImportProfile::create([
                'name' => 'Custom Profile',
                'source_system' => 'Custom',
                'version' => '1.0',
                'is_default' => false,
                'mappings' => [
                    'employee_code' => ['custom code', 'custom employee code'],
                ],
                'matching_weights' => $defaultWeights,
            ]);
        }
    }

    /**
     * Resolve a spreadsheet header value to its canonical AMS field name.
     */
    public function getCanonicalKey(string $headerName, ImportProfile $profile): ?string
    {
        $normalized = strtolower(str_replace([' ', '_', '-', '.', '/'], '', trim($headerName)));
        foreach ($profile->mappings as $canonical => $aliases) {
            foreach ($aliases as $alias) {
                $normAlias = strtolower(str_replace([' ', '_', '-', '.', '/'], '', trim($alias)));
                if ($normalized === $normAlias) {
                    return $canonical;
                }
            }
        }
        return null;
    }

    /**
     * Resolve secondary/profile fields using a fallback mapping list.
     */
    public function getFallbackCanonicalKey(string $headerName): ?string
    {
        return $this->matchingService->getFallbackCanonicalKey($headerName);
    }

    /**
     * Detect the most matching Import Profile based on sheet headers.
     */
    public function detectProfile(array $headerNames): ImportProfile
    {
        $this->ensureDefaultProfilesExist();
        $profiles = ImportProfile::all();

        $bestProfile = null;
        $highestMatchCount = -1;

        foreach ($profiles as $profile) {
            $matchCount = 0;
            foreach ($headerNames as $headerName) {
                if ($headerName === null) {
                    continue;
                }
                if ($this->getCanonicalKey(trim($headerName), $profile) !== null) {
                    $matchCount++;
                }
            }

            if ($matchCount > $highestMatchCount) {
                $highestMatchCount = $matchCount;
                $bestProfile = $profile;
            }
        }

        return $bestProfile ?? ImportProfile::where('is_default', true)->first();
    }

    /**
     * Analyze profile matching confidence and headers for Auto-Detect display.
     */
    public function analyzeDetection(array $headerRow, ImportProfile $profile): array
    {
        $matched = [];
        $missing = [];
        $unknown = [];

        $profileKeys = array_keys($profile->mappings);
        $mappedCanonicals = [];

        foreach ($headerRow as $colLetter => $headerName) {
            if ($headerName === null) {
                continue;
            }
            $name = trim($headerName);
            $canonical = $this->getCanonicalKey($name, $profile);

            if (!$canonical) {
                $canonical = $this->getFallbackCanonicalKey($name);
            }

            if ($canonical) {
                $matched[] = $name;
                $mappedCanonicals[$canonical] = true;
            } else {
                $unknown[] = $name;
            }
        }

        // Check missing mapped columns
        foreach ($profileKeys as $key) {
            if (!isset($mappedCanonicals[$key])) {
                $missing[] = $key;
            }
        }

        $totalHeaders = count(array_filter($headerRow));
        $confidence = $totalHeaders > 0 ? intval((count($matched) / $totalHeaders) * 100) : 0;

        return [
            'profile_id' => $profile->id,
            'name' => $profile->name,
            'source_system' => $profile->source_system,
            'version' => $profile->version,
            'confidence' => $confidence,
            'matched_headers' => $matched,
            'missing_headers' => $missing,
            'unknown_headers' => $unknown,
        ];
    }

    /**
     * Generate preview information and a spreadsheet health report.
     */
    public function preview(string $filePath, string $mode, array $updateCategories, $profileId = null, array $approvedMappings = []): array
    {
        $this->ensureDefaultProfilesExist();

        if (!file_exists($filePath)) {
            throw new \Exception("File not found: {$filePath}");
        }

        try {
            $spreadsheet = IOFactory::load($filePath);
        } catch (\Exception $e) {
            throw new \Exception("Failed to load spreadsheet: " . $e->getMessage());
        }

        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $headerRow = $rows[1] ?? null;
        if (!$headerRow) {
            throw new \Exception("Spreadsheet is empty or missing headers.");
        }

        // Resolve Profile
        if ($profileId && $profileId !== 'auto') {
            $profile = ImportProfile::findOrFail($profileId);
        } else {
            $profile = $this->detectProfile($headerRow);
        }

        $autoDetectSummary = $this->analyzeDetection($headerRow, $profile);

        // Map canonical columns to spreadsheet column letters
        $canonicalMap = [];
        $headerCounts = [];
        $unknownColumns = [];

        foreach ($headerRow as $columnLetter => $headerName) {
            if ($headerName !== null) {
                $name = trim($headerName);
                $canonical = $this->getCanonicalKey($name, $profile);

                if (!$canonical) {
                    $canonical = $this->getFallbackCanonicalKey($name);
                }

                if ($canonical) {
                    if (!isset($headerCounts[$canonical])) {
                        $headerCounts[$canonical] = 0;
                    }
                    $headerCounts[$canonical]++;
                    
                    $key = $canonical;
                    if ($headerCounts[$canonical] > 1) {
                        $key = $canonical . '.' . ($headerCounts[$canonical] - 1);
                    }
                    $canonicalMap[$key] = $columnLetter;
                } else {
                    $unknownColumns[] = $name;
                }
            }
        }

        // Spreadsheet Health checks lists
        $missingRequired = [];
        $duplicateEmployeeIds = [];
        $duplicateEmails = [];
        $duplicateNames = [];
        $invalidSalaryValues = [];
        $invalidLeaveValues = [];
        $blankRequiredCells = [];
        $invalidDates = [];

        // Required headers validation
        $essential = ['employee_code'];
        if ($mode === 'create') {
            $essential[] = 'official_email';
            $essential[] = 'full_name';
        }

        foreach ($essential as $field) {
            if (!isset($canonicalMap[$field])) {
                $missingRequired[] = $field;
            }
        }

        // Keep track of values inside spreadsheet to check for duplicates
        $seenEmployeeCodes = [];
        $seenEmails = [];
        $seenNames = [];

        $matchedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $notFoundCount = 0;
        $suggestedMatchesCount = 0;
        $needsManualReviewCount = 0;

        $suggestedEmployeeMatches = [];
        $needsManualReview = [];
        $validationErrors = [];

        $hasSalaryCat = in_array('base_salary', $updateCategories);
        $hasLeavesCat = in_array('leave_balances', $updateCategories);

        // Preload departments
        $departments = Department::all()->pluck('id', 'name')->toArray();

        foreach ($rows as $rowIndex => $row) {
            if ($rowIndex === 1) {
                continue;
            }

            // Skip empty rows
            $nonEmptyCells = array_filter($row, fn($val) => $val !== null && trim((string)$val) !== '');
            if (empty($nonEmptyCells)) {
                continue;
            }

            $externalCode = $this->getRowVal($row, $canonicalMap, 'employee_code');
            $email = $this->getRowVal($row, $canonicalMap, 'official_email');
            $fullName = $this->getRowVal($row, $canonicalMap, 'full_name');
            $departmentName = $this->getRowVal($row, $canonicalMap, 'department');
            $designation = $this->getRowVal($row, $canonicalMap, 'designation');
            $joiningDateVal = $this->getRowVal($row, $canonicalMap, 'joining_date');
            $mobileNo = $this->getRowVal($row, $canonicalMap, 'mobile_no');

            $cleanEmail = $email ? strtolower(trim($email)) : null;
            $nameKey = $fullName ? strtolower(trim($fullName)) : null;
            $standardizedCode = $this->matchingService->standardizeEmployeeId($externalCode);

            // 1. Validate Blank Required Cells
            if ($externalCode === null || trim((string)$externalCode) === '') {
                $blankRequiredCells[] = [
                    'row' => $rowIndex,
                    'field' => 'Employee Code',
                ];
                $validationErrors[] = [
                    'row' => $rowIndex,
                    'reason' => "Row skipped: Employee Code is blank.",
                ];
                continue;
            }

            if ($mode === 'create') {
                if ($email === null || trim((string)$email) === '') {
                    $blankRequiredCells[] = [
                        'row' => $rowIndex,
                        'field' => 'Official Email ID',
                    ];
                }
                if ($fullName === null || trim((string)$fullName) === '') {
                    $blankRequiredCells[] = [
                        'row' => $rowIndex,
                        'field' => 'Full Name',
                    ];
                }
            }

            // 2. Validate Duplicate Employee Codes
            if (isset($seenEmployeeCodes[$externalCode])) {
                $duplicateEmployeeIds[] = [
                    'row' => $rowIndex,
                    'original_row' => $seenEmployeeCodes[$externalCode],
                    'value' => $externalCode,
                    'external_identifier' => $externalCode,
                ];
            }
            $seenEmployeeCodes[$externalCode] = $rowIndex;

            // 3. Validate Duplicate Emails
            if ($email) {
                $emailKey = strtolower(trim($email));
                if (isset($seenEmails[$emailKey])) {
                    $duplicateEmails[] = [
                        'row' => $rowIndex,
                        'original_row' => $seenEmails[$emailKey],
                        'value' => $email,
                    ];
                }
                $seenEmails[$emailKey] = $rowIndex;
            }

            // 4. Validate Duplicate Names
            if ($fullName) {
                $nameKey = strtolower(trim($fullName));
                if (isset($seenNames[$nameKey])) {
                    $duplicateNames[] = [
                        'row' => $rowIndex,
                        'original_row' => $seenNames[$nameKey],
                        'value' => $fullName,
                    ];
                }
                $seenNames[$nameKey] = $rowIndex;
            }

            // 5. Validate Invalid Salary values
            if (isset($canonicalMap['base_salary'])) {
                $salaryVal = $this->getRowVal($row, $canonicalMap, 'base_salary');
                if ($salaryVal !== null && trim((string)$salaryVal) !== '') {
                    $parsed = $this->matchingService->parseNumeric($salaryVal);
                    if ($parsed === null || $parsed < 0) {
                        $invalidSalaryValues[] = [
                            'row' => $rowIndex,
                            'value' => $salaryVal,
                        ];
                    }
                }
            }

            // 6. Validate Invalid Leave values
            $leaveFields = ['planned_leave', 'unplanned_leave', 'paternity_leave', 'maternity_leave', 'compensatory_leave', 'pending_leave', 'utilized_leave', 'carry_forward', 'remaining_leave'];
            foreach ($leaveFields as $field) {
                if (isset($canonicalMap[$field])) {
                    $val = $this->getRowVal($row, $canonicalMap, $field);
                    if ($val !== null && trim((string)$val) !== '') {
                        $parsed = $this->matchingService->parseNumeric($val);
                        if ($parsed === null || $parsed < 0) {
                            $invalidLeaveValues[] = [
                                'row' => $rowIndex,
                                'field' => $field,
                                'value' => $val,
                            ];
                        }
                    }
                }
            }

            // 7. Validate Invalid Dates
            $dateFields = ['salary_effective_date', 'joining_date', 'date_of_birth', 'date_of_marriage', 'date_of_gratuity', 'contract_end_date', 'previous_from_date', 'previous_to_date', 'probation_confirm_date', 'separation_date', 'last_working_day'];
            foreach ($dateFields as $field) {
                if (isset($canonicalMap[$field])) {
                    $val = $this->getRowVal($row, $canonicalMap, $field);
                    if ($val !== null && trim((string)$val) !== '') {
                        $parsed = $this->matchingService->parseDate($val);
                        if ($parsed === null) {
                            $invalidDates[] = [
                                'row' => $rowIndex,
                                'field' => $field,
                                'value' => $val,
                            ];
                        }
                    }
                }
            }

            // CALL MATCHING ENGINE FOR WEIGHTED SCORE
            $rowData = [
                'employee_code' => $externalCode,
                'official_email' => $email,
                'full_name' => $fullName,
                'department' => $departmentName,
                'designation' => $designation,
                'joining_date' => $joiningDateVal,
                'mobile_no' => $mobileNo,
            ];

            $matchResults = $this->matchingService->matchRow($rowData, $profile->source_system, $profile);
            $user = $matchResults['matched_user'];
            $confidenceScore = $matchResults['score'];
            $resolutionMethod = count($matchResults['breakdown']) > 0 ? implode(' + ', $matchResults['breakdown']) : 'Auto matched';

            // Check if manually mapped or skipped by administrator in current session
            if (isset($approvedMappings[$externalCode])) {
                $mappedVal = $approvedMappings[$externalCode];
                if ($mappedVal === 'skip' || empty($mappedVal)) {
                    $skippedCount++;
                    continue; // Skip this row entirely
                } else {
                    $mappedUser = User::find($mappedVal);
                    if ($mappedUser) {
                        $user = $mappedUser;
                        $confidenceScore = 100;
                        $resolutionMethod = 'Manually verified by administrator';
                    }
                }
            }

            if ($user) {
                // Highly Confident Auto Match
                $matchedCount++;
                if ($mode === 'create') {
                    $skippedCount++;
                } else {
                    $hasChange = false;
                    $rowErrors = [];

                    // Base Salary change checks
                    if ($hasSalaryCat && isset($canonicalMap['base_salary'])) {
                        $salaryVal = $this->getRowVal($row, $canonicalMap, 'base_salary');
                        if ($salaryVal !== null && trim((string)$salaryVal) !== '') {
                            $parsed = $this->matchingService->parseNumeric($salaryVal);
                            if ($parsed !== null && $parsed >= 0) {
                                $curr = $user->payrollProfile?->base_salary;
                                if ($curr === null || (float)$curr !== (float)$parsed) {
                                    $hasChange = true;
                                }
                            }
                        }
                    }

                    // Leave balance change checks
                    if ($hasLeavesCat) {
                        foreach ($leaveFields as $field) {
                            if (isset($canonicalMap[$field])) {
                                $val = $this->getRowVal($row, $canonicalMap, $field);
                                if ($val !== null && trim((string)$val) !== '') {
                                    $parsed = $this->matchingService->parseNumeric($val);
                                    if ($parsed !== null && $parsed >= 0) {
                                        $curr = $user->leaveBalance?->$field;
                                        if ($curr === null || (float)$curr !== (float)$parsed) {
                                            $hasChange = true;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if ($hasChange) {
                        $updatedCount++;
                    } else {
                        $skippedCount++;
                    }
                }

                // Add to recommended list for visualization
                $suggestedMatchesCount++;
                $suggestedEmployeeMatches[] = [
                    'row' => $rowIndex,
                    'external_code' => $externalCode,
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'employee_id' => $user->employee_id,
                    'email' => $user->email,
                    'confidence' => $confidenceScore,
                    'resolution_method' => $resolutionMethod,
                ];

            } else {
                // Score falls below auto-match threshold (Employee Verification Required)
                $hasPotentialDuplicate = false;
                if ($mode === 'create') {
                    foreach ($matchResults['candidates'] as $cand) {
                        foreach ($cand['breakdown'] as $b) {
                            if (str_contains($b, 'Name') || str_contains($b, 'Email') || str_contains($b, 'Employee ID')) {
                                $hasPotentialDuplicate = true;
                                break;
                            }
                        }
                        if ($hasPotentialDuplicate) {
                            break;
                        }
                    }
                }

                if ($mode === 'create' && !$hasPotentialDuplicate) {
                    // Create mode and absolutely zero match: expected new registration
                    $rowErrors = [];
                    if (empty($email)) {
                        $rowErrors[] = "Missing Official Email ID";
                    }
                    if (empty($fullName)) {
                        $rowErrors[] = "Missing Full Name";
                    }
                    
                    $deptName = $this->getRowVal($row, $canonicalMap, 'department');
                    if (!empty($deptName)) {
                        $deptNameTrimmed = strtolower(trim($deptName));
                        $hasDept = false;
                        foreach ($departments as $nameKey => $idVal) {
                            if (strtolower($nameKey) === $deptNameTrimmed) {
                                $hasDept = true;
                                break;
                            }
                        }
                        if (!$hasDept) {
                            $rowErrors[] = "Department not found: '{$deptName}'";
                        }
                    } else {
                        $rowErrors[] = "Missing Department";
                    }

                    if (!empty($rowErrors)) {
                        foreach ($rowErrors as $err) {
                            $validationErrors[] = [
                                'row' => $rowIndex,
                                'employee_code' => $externalCode,
                                'reason' => $err,
                            ];
                        }
                    } else {
                        $updatedCount++;
                    }
                } else {
                    // Requires manual review card (needs administrator review)
                    $needsManualReviewCount++;

                    // Prepare spreadsheet vs matched candidate values breakdown
                    $topCandidate = $matchResults['candidates'][0] ?? null;
                    $matchedValues = [];
                    $missingValues = [];

                    if ($topCandidate) {
                        $tcUser = $topCandidate['user'];
                        $matchedValues = [
                            'Name' => $this->matchingService->normalizeString($tcUser->name) === $nameKey ? $tcUser->name : null,
                            'Email' => strtolower($tcUser->email) === $cleanEmail ? $tcUser->email : null,
                            'Employee ID' => $tcUser->employee_id === $standardizedCode ? $tcUser->employee_id : null,
                        ];

                        if ($email && strtolower($tcUser->email) !== $cleanEmail) {
                            $missingValues['Email'] = "Spreadsheet has '{$email}', Candidate has '{$tcUser->email}'";
                        }
                        if ($name && $this->matchingService->normalizeString($tcUser->name) !== $nameKey) {
                            $missingValues['Name'] = "Spreadsheet has '{$name}', Candidate has '{$tcUser->name}'";
                        }
                    }

                    $candidatesList = [];
                    foreach ($matchResults['candidates'] as $candidate) {
                        $cUser = $candidate['user'];
                        
                        $candBreakdown = $candidate['breakdown'];
                        $matchedFields = [
                            'Name' => false,
                            'Official Email' => false,
                            'Employee ID' => false,
                            'Department' => false,
                        ];
                        foreach ($candBreakdown as $b) {
                            if (str_contains($b, 'Name')) $matchedFields['Name'] = true;
                            if (str_contains($b, 'Email')) $matchedFields['Official Email'] = true;
                            if (str_contains($b, 'Employee ID')) $matchedFields['Employee ID'] = true;
                            if (str_contains($b, 'Department')) $matchedFields['Department'] = true;
                        }

                        $candidatesList[] = [
                            'id' => $cUser->id,
                            'name' => $cUser->name,
                            'employee_id' => $cUser->employee_id,
                            'email' => $cUser->email ?? 'N/A',
                            'department' => $cUser->department?->name ?? 'N/A',
                            'designation' => $cUser->employeeProfile?->designation ?? 'N/A',
                            'score' => $candidate['score'],
                            'breakdown' => $candidate['breakdown'],
                            'matched_fields' => $matchedFields,
                        ];
                    }

                    $highestScore = $topCandidate ? $topCandidate['score'] : 0;
                    $reason = "Highest match score " . ($topCandidate ? "({$topCandidate['score']})" : "(0)") . " is below confidence threshold ({$matchResults['threshold']}).";

                    // Diagnostics check
                    $hasIdMatch = false;
                    foreach ($matchResults['candidates'] as $cand) {
                        $tcUser = $cand['user'];
                        if ($standardizedCode && ($tcUser->employee_id === $standardizedCode || $tcUser->employee_id === $externalCode)) {
                            $hasIdMatch = true;
                        }
                    }

                    $hasExtMatch = false;
                    if ($externalCode) {
                        $hasExtMatch = EmployeeExternalIdentifier::where('source', $profile->source_system)
                            ->where('external_identifier', $externalCode)
                            ->where('is_active', true)
                            ->exists();
                    }

                    $possibleNameMatchesCount = 0;
                    foreach ($candidatesList as $cand) {
                        if ($cand['matched_fields']['Name']) {
                            $possibleNameMatchesCount++;
                        }
                    }

                    $diagnostics = [];
                    $diagnostics[] = $hasIdMatch ? 'Employee ID matched' : 'No Employee ID match';
                    $diagnostics[] = $hasExtMatch ? 'External Identifier matched' : 'No External Identifier match';
                    if ($possibleNameMatchesCount === 1) {
                        $diagnostics[] = 'One possible Name match';
                    } elseif ($possibleNameMatchesCount > 1) {
                        $diagnostics[] = "{$possibleNameMatchesCount} possible Name matches";
                    } else {
                        $diagnostics[] = 'No Name match';
                    }
                    $diagnostics[] = 'Administrator verification required';

                    $needsManualReview[] = [
                        'row' => $rowIndex,
                        'external_code' => $externalCode,
                        'name' => $fullName ?? 'N/A',
                        'email' => $email ?? 'N/A',
                        'department' => $departmentName ?? 'N/A',
                        'designation' => $designation ?? 'N/A',
                        'joining_date' => $joiningDateVal ?? 'N/A',
                        'mobile_no' => $mobileNo ?? 'N/A',
                        'confidence' => $highestScore,
                        'reason_for_failure' => $reason,
                        'candidates' => $candidatesList,
                        'spreadsheet_values' => $rowData,
                        'matched_values' => array_filter($matchedValues),
                        'missing_values' => $missingValues,
                        'diagnostics' => $diagnostics,
                    ];
                }
            }
        }

        // Build list of columns to be updated and ignored
        $fieldsToUpdate = [];
        $fieldsIgnored = [];

        foreach ($profile->mappings as $canonical => $aliases) {
            $hasCol = isset($canonicalMap[$canonical]);
            if ($canonical === 'employee_code' || $canonical === 'official_email' || $canonical === 'full_name' || $canonical === 'department') {
                if ($hasCol) {
                    $fieldsToUpdate[] = $canonical;
                }
                continue;
            }

            if ($canonical === 'base_salary' || $canonical === 'salary_effective_date' || $canonical === 'payroll_enabled') {
                if ($hasSalaryCat) {
                    if ($hasCol) {
                        $fieldsToUpdate[] = $canonical;
                    } else {
                        $fieldsIgnored[] = "{$canonical} (Column missing in sheet)";
                    }
                } else {
                    $fieldsIgnored[] = "{$canonical} (Category unchecked)";
                }
                continue;
            }

            // Leave fields
            if ($hasLeavesCat) {
                if ($hasCol) {
                    $fieldsToUpdate[] = $canonical;
                } else {
                    $fieldsIgnored[] = "{$canonical} (Column missing in sheet)";
                }
            } else {
                $fieldsIgnored[] = "{$canonical} (Category unchecked)";
            }
        }

        // Spreadsheet Health summary
        $health = [
            'has_errors' => count($missingRequired) > 0 || count($duplicateEmployeeIds) > 0 || count($duplicateEmails) > 0 || count($duplicateNames) > 0 || count($invalidSalaryValues) > 0 || count($invalidLeaveValues) > 0 || count($blankRequiredCells) > 0 || count($invalidDates) > 0,
            'header_validation' => count($missingRequired) > 0 ? 'MISSING_REQUIRED' : 'OK',
            'missing_required_columns' => $missingRequired,
            'unknown_columns' => $unknownColumns,
            'duplicate_employee_identifiers' => $duplicateEmployeeIds,
            'duplicate_external_identifiers' => $duplicateEmployeeIds,
            'duplicate_emails' => $duplicateEmails,
            'duplicate_names' => $duplicateNames,
            'invalid_salary_values' => $invalidSalaryValues,
            'invalid_leave_values' => $invalidLeaveValues,
            'blank_required_cells' => $blankRequiredCells,
            'invalid_dates' => $invalidDates,
        ];

        return [
            'matched_count' => $matchedCount,
            'updated_count' => $updatedCount,
            'skipped_count' => $skippedCount,
            'not_found_count' => $notFoundCount,
            'suggested_matches_count' => $suggestedMatchesCount,
            'needs_manual_review_count' => $needsManualReviewCount,
            'suggested_employee_matches' => $suggestedEmployeeMatches,
            'needs_manual_review' => $needsManualReview,
            'validation_errors' => $validationErrors,
            'fields_to_update' => $fieldsToUpdate,
            'fields_ignored' => $fieldsIgnored,
            'rows_count' => count($rows) - 1,
            'spreadsheet_health' => $health,
            'profile' => [
                'id' => $profile->id,
                'name' => $profile->name,
                'source_system' => $profile->source_system,
                'version' => $profile->version,
            ],
            'auto_detect_summary' => $autoDetectSummary,
        ];
    }

    /**
     * Confirm and execute the spreadsheet import.
     * Guaranteed transaction-safe outer wrapper.
     */
    public function import(
        string $filePath,
        string $mode = 'create',
        array $updateCategories = [],
        ?int $runByUserId = null,
        array $approvedMappings = [],
        ?int $profileId = null,
        array $createMappings = []
    ): array {
        $startTime = microtime(true);
        $this->ensureDefaultProfilesExist();

        $defaultPassword = config('employees.default_employee_password');
        if (empty($defaultPassword)) {
            throw new \Exception("The DEFAULT_EMPLOYEE_PASSWORD environment variable is not configured.");
        }

        if (!file_exists($filePath)) {
            throw new \Exception("File not found: {$filePath}");
        }

        try {
            $spreadsheet = IOFactory::load($filePath);
        } catch (\Exception $e) {
            throw new \Exception("Failed to load spreadsheet: " . $e->getMessage());
        }

        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $headerRow = $rows[1] ?? null;
        if (!$headerRow) {
            throw new \Exception("Spreadsheet is empty.");
        }

        // Resolve Profile
        if ($profileId && $profileId !== 'auto') {
            $profile = ImportProfile::findOrFail($profileId);
        } else {
            $profile = $this->detectProfile($headerRow);
        }

        // Mappings
        $canonicalMap = [];
        $headerCounts = [];
        foreach ($headerRow as $columnLetter => $headerName) {
            if ($headerName !== null) {
                $canonical = $this->getCanonicalKey(trim($headerName), $profile);
                if (!$canonical) {
                    $canonical = $this->getFallbackCanonicalKey(trim($headerName));
                }
                if ($canonical) {
                    if (!isset($headerCounts[$canonical])) {
                        $headerCounts[$canonical] = 0;
                    }
                    $headerCounts[$canonical]++;
                    
                    $key = $canonical;
                    if ($headerCounts[$canonical] > 1) {
                        $key = $canonical . '.' . ($headerCounts[$canonical] - 1);
                    }
                    $canonicalMap[$key] = $columnLetter;
                }
            }
        }

        $rowsProcessed = 0;
        $createdCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $errors = [];
        $processedUsers = []; // rowIndex => User

        $hasSalaryCat = in_array('base_salary', $updateCategories);
        $hasLeavesCat = in_array('leave_balances', $updateCategories);

        // ATOMIC TRANSACTION: Enforce absolute data safety rollback of the entire import
        DB::beginTransaction();

        try {
            // Pre-process approved mappings - Only create DB mappings if explicitly checked
            foreach ($approvedMappings as $extCode => $targetUserId) {
                if (!empty($targetUserId) && $targetUserId !== 'skip') {
                    $shouldCreate = true;
                    if (isset($createMappings['_ui'])) {
                        $shouldCreate = isset($createMappings[$extCode]) && $createMappings[$extCode];
                    }
                    if ($shouldCreate) {
                        EmployeeExternalIdentifier::updateOrCreate(
                            [
                                'source' => $profile->source_system,
                                'external_identifier' => $extCode,
                            ],
                            [
                                'user_id' => $targetUserId,
                                'is_active' => true,
                                'verified_by_id' => $runByUserId,
                                'verified_at' => now(),
                                'notes' => 'Approved by administrator during import verification',
                            ]
                        );
                    }
                }
            }

            if ($mode === 'create') {
                // PASS 1: Create or Resolve Users
                foreach ($rows as $rowIndex => $row) {
                    if ($rowIndex === 1) {
                        continue;
                    }

                    $nonEmptyCells = array_filter($row, fn($val) => $val !== null && trim((string)$val) !== '');
                    if (empty($nonEmptyCells)) {
                        continue;
                    }

                    $rowsProcessed++;

                    $externalCode = $this->getRowVal($row, $canonicalMap, 'employee_code');
                    $officialEmail = $this->getRowVal($row, $canonicalMap, 'official_email');
                    $fullName = $this->getRowVal($row, $canonicalMap, 'full_name');

                    if (empty($externalCode)) {
                        throw new \Exception("Validation Failure at Row {$rowIndex}: Employee Code is missing.");
                    }

                    $standardizedId = $this->matchingService->standardizeEmployeeId($externalCode);

                    // Resolve user using the same matching engine logic or pre-approved mappings
                    $user = null;
                    
                    $mapping = EmployeeExternalIdentifier::where('source', $profile->source_system)
                        ->where('external_identifier', $externalCode)
                        ->first();
                    if ($mapping) {
                        $user = User::find($mapping->user_id);
                    }
                    if (!$user) {
                        $user = User::where('employee_id', $standardizedId)
                            ->orWhere('employee_id', $externalCode)
                            ->first();
                    }
                    if (!$user && !empty($officialEmail)) {
                        $user = User::where('email', trim($officialEmail))->first();
                    }

                    if ($user) {
                        // Legacy hybrid behavior: Update core profile
                        $statusVal = $this->getRowVal($row, $canonicalMap, 'status');
                        if (!empty($statusVal)) {
                            $trimmedStatus = strtolower(trim($statusVal));
                            if (in_array($trimmedStatus, ['active', 'probation', 'confirmed'])) {
                                $user->status = 'active';
                            }
                        }

                        $deptName = $this->getRowVal($row, $canonicalMap, 'department');
                        if (!empty($deptName)) {
                            $department = Department::whereRaw('LOWER(name) = ?', [strtolower(trim($deptName))])->first();
                            if ($department) {
                                $user->department_id = $department->id;
                            }
                        }
                        $user->save();

                        // Map profile details
                        $profileData = $this->getProfileDataMap($row, $canonicalMap);
                        $profileData = array_filter($profileData, fn($val) => $val !== null && trim((string)$val) !== '');

                        EmployeeProfile::updateOrCreate(['user_id' => $user->id], $profileData);
                        $updatedCount++;

                        // Import payroll/leave profiles for existing users if present in spreadsheet
                        $payrollProfile = PayrollProfile::firstOrCreate(['user_id' => $user->id]);
                        $leaveBalance = LeaveBalance::firstOrCreate(['user_id' => $user->id]);

                        // Import payroll data if present
                        $baseSalaryVal = $this->getRowVal($row, $canonicalMap, 'base_salary');
                        $effectiveDateVal = $this->getRowVal($row, $canonicalMap, 'salary_effective_date');
                        $payrollEnabledVal = $this->getRowVal($row, $canonicalMap, 'payroll_enabled');

                        $baseSalary = ($baseSalaryVal !== null && trim((string)$baseSalaryVal) !== '') ? $this->matchingService->parseNumeric($baseSalaryVal) : null;
                        $effectiveDate = ($effectiveDateVal !== null && trim((string)$effectiveDateVal) !== '') ? $this->matchingService->parseDate($effectiveDateVal) : ($user->joining_date ? $user->joining_date->format('Y-m-d') : null);
                        $payrollEnabled = ($payrollEnabledVal !== null && trim((string)$payrollEnabledVal) !== '') ? $this->parseBoolean($payrollEnabledVal) : true;

                        if ($baseSalary !== null || $effectiveDate !== null) {
                            $resolvedEffective = $effectiveDate ?? ($user->joining_date ? $user->joining_date->format('Y-m-d') : now()->format('Y-m-d'));
                            $payrollProfile->update([
                                'base_salary' => $baseSalary ?? $payrollProfile->base_salary,
                                'salary_effective_date' => $resolvedEffective,
                                'payroll_enabled' => $payrollEnabled,
                                'last_imported_at' => now(),
                                'imported_by_id' => $runByUserId,
                                'import_source' => 'Imported',
                            ]);

                            if ($baseSalary !== null) {
                                $payrollProfile->recordSalaryRevision(
                                    $baseSalary,
                                    $resolvedEffective,
                                    'Salary setup during import',
                                    $runByUserId,
                                    'Imported'
                                );
                            }
                        }

                        // Import leave balances if present
                        $leaveFields = ['planned_leave', 'unplanned_leave', 'paternity_leave', 'maternity_leave', 'compensatory_leave', 'pending_leave', 'utilized_leave', 'carry_forward', 'remaining_leave'];
                        $leaveUpdate = [
                            'last_imported_at' => now(),
                            'imported_by_id' => $runByUserId,
                            'import_source' => 'Imported',
                        ];
                        $hasLeaveVal = false;

                        foreach ($leaveFields as $field) {
                            $val = $this->getRowVal($row, $canonicalMap, $field);
                            if ($val !== null && trim((string)$val) !== '') {
                                $leaveUpdate[$field] = $this->matchingService->parseNumeric($val) ?? 0.00;
                                $hasLeaveVal = true;
                            }
                        }

                        if ($hasLeaveVal) {
                            $oldRemaining = (float)($leaveBalance->remaining_leave ?? 0.00);
                            $leaveBalance->update($leaveUpdate);
                            
                            if (isset($leaveUpdate['remaining_leave'])) {
                                $newRemaining = (float)$leaveUpdate['remaining_leave'];
                                $diff = $newRemaining - $oldRemaining;

                                if ((float)$diff !== 0.0) {
                                    $user->leave_balance = $newRemaining;
                                    $user->save();

                                    LeaveLedgerEntry::create([
                                        'user_id' => $user->id,
                                        'amount' => $diff,
                                        'type' => 'adjustment',
                                        'description' => "Remaining Leave adjusted via Excel import: {$oldRemaining} -> {$newRemaining}",
                                    ]);
                                }
                            }
                        }

                        $processedUsers[$rowIndex] = $user;
                        continue;
                    }

                    // Create mode - Create new employee
                    if (empty($officialEmail)) {
                        $errors[] = [
                            'row' => $rowIndex,
                            'reason' => "Row skipped: Email is missing for new employee.",
                        ];
                        $skippedCount++;
                        continue;
                    }
                    if (empty($fullName)) {
                        $errors[] = [
                            'row' => $rowIndex,
                            'reason' => "Row skipped: Name is missing for new employee.",
                        ];
                        $skippedCount++;
                        continue;
                    }

                    $deptName = $this->getRowVal($row, $canonicalMap, 'department');
                    $department = null;
                    if (!empty($deptName)) {
                        $department = Department::whereRaw('LOWER(name) = ?', [strtolower(trim($deptName))])->first();
                    }
                    if (!$department) {
                        $errors[] = [
                            'row' => $rowIndex,
                            'reason' => "Row skipped: Department '{$deptName}' not found.",
                        ];
                        $skippedCount++;
                        continue;
                    }

                    $statusVal = $this->getRowVal($row, $canonicalMap, 'status');
                    $mappedStatus = 'active';
                    if (!empty($statusVal)) {
                        $trimmedStatus = strtolower(trim($statusVal));
                        if (!in_array($trimmedStatus, ['active', 'probation', 'confirmed'])) {
                            $errors[] = [
                                'row' => $rowIndex,
                                'reason' => "Row skipped: Invalid status '{$statusVal}'.",
                            ];
                            $skippedCount++;
                            continue;
                        }
                    }

                    $mobileNo = $this->matchingService->cleanPhoneNumber($this->getRowVal($row, $canonicalMap, 'mobile_no'));
                    $joiningDate = $this->matchingService->parseDate($this->getRowVal($row, $canonicalMap, 'joining_date'));

                    $user = new User();
                    $user->employee_id = $standardizedId;
                    $user->name = trim($fullName);
                    $user->email = trim($officialEmail);
                    $user->role = 'employee';
                    $user->status = $mappedStatus;
                    $user->phone = $mobileNo;
                    $user->joining_date = $joiningDate;
                    $user->department_id = $department->id;
                    $user->must_change_password = true;
                    $user->password = \Illuminate\Support\Facades\Hash::make($defaultPassword);
                    $user->save();

                    $createdCount++;

                    // Create default mapping for new user
                    EmployeeExternalIdentifier::updateOrCreate(
                        [
                            'source' => $profile->source_system,
                            'external_identifier' => $externalCode,
                        ],
                        [
                            'user_id' => $user->id,
                            'is_active' => true,
                            'verified_by_id' => $runByUserId,
                            'verified_at' => now(),
                            'notes' => 'Auto-mapped on creation',
                        ]
                    );

                    $profileData = $this->getProfileDataMap($row, $canonicalMap);
                    EmployeeProfile::updateOrCreate(['user_id' => $user->id], $profileData);

                    \App\Services\LeaveBalanceService::initializeUser($user);
                    $user->syncBirthdayCredits();

                    // Load profiles
                    $payrollProfile = $user->payrollProfile;
                    $leaveBalance = $user->leaveBalance;

                    // Import payroll data if present
                    $baseSalaryVal = $this->getRowVal($row, $canonicalMap, 'base_salary');
                    $effectiveDateVal = $this->getRowVal($row, $canonicalMap, 'salary_effective_date');
                    $payrollEnabledVal = $this->getRowVal($row, $canonicalMap, 'payroll_enabled');

                    $baseSalary = ($baseSalaryVal !== null && trim((string)$baseSalaryVal) !== '') ? $this->matchingService->parseNumeric($baseSalaryVal) : null;
                    $effectiveDate = ($effectiveDateVal !== null && trim((string)$effectiveDateVal) !== '') ? $this->matchingService->parseDate($effectiveDateVal) : ($user->joining_date ? $user->joining_date->format('Y-m-d') : null);
                    $payrollEnabled = ($payrollEnabledVal !== null && trim((string)$payrollEnabledVal) !== '') ? $this->parseBoolean($payrollEnabledVal) : true;

                    if ($baseSalary !== null || $effectiveDate !== null) {
                        $resolvedEffective = $effectiveDate ?? ($user->joining_date ? $user->joining_date->format('Y-m-d') : now()->format('Y-m-d'));
                        $payrollProfile->update([
                            'base_salary' => $baseSalary ?? $payrollProfile->base_salary,
                            'salary_effective_date' => $resolvedEffective,
                            'payroll_enabled' => $payrollEnabled,
                            'last_imported_at' => now(),
                            'imported_by_id' => $runByUserId,
                            'import_source' => 'Imported',
                        ]);

                        if ($baseSalary !== null) {
                            $payrollProfile->recordSalaryRevision(
                                $baseSalary,
                                $resolvedEffective,
                                'Salary setup during import',
                                $runByUserId,
                                'Imported'
                            );
                        }
                    }

                    // Import leave balances if present
                    $leaveFields = ['planned_leave', 'unplanned_leave', 'paternity_leave', 'maternity_leave', 'compensatory_leave', 'pending_leave', 'utilized_leave', 'carry_forward', 'remaining_leave'];
                    $leaveUpdate = [
                        'last_imported_at' => now(),
                        'imported_by_id' => $runByUserId,
                        'import_source' => 'Imported',
                    ];
                    $hasLeaveVal = false;

                    foreach ($leaveFields as $field) {
                        $val = $this->getRowVal($row, $canonicalMap, $field);
                        if ($val !== null && trim((string)$val) !== '') {
                            $leaveUpdate[$field] = $this->matchingService->parseNumeric($val) ?? 0.00;
                            $hasLeaveVal = true;
                        }
                    }

                    if ($hasLeaveVal) {
                        $leaveBalance->update($leaveUpdate);
                        if (isset($leaveUpdate['remaining_leave'])) {
                            $user->leave_balance = $leaveUpdate['remaining_leave'];
                            $user->save();

                            LeaveLedgerEntry::create([
                                'user_id' => $user->id,
                                'amount' => $leaveUpdate['remaining_leave'] - 2.00,
                                'type' => 'adjustment',
                                'description' => 'Initial leave balance adjusted during creation.',
                            ]);
                        }
                    }

                    $processedUsers[$rowIndex] = $user;
                }

                // PASS 2: Resolve and update Reporting Managers
                $allUsers = User::select('id', 'employee_id')->whereNotNull('employee_id')->get();
                $userLookup = [];
                foreach ($allUsers as $u) {
                    if (preg_match('/\d+/', $u->employee_id, $idMatches)) {
                        $userLookup[(int)$idMatches[0]] = $u->id;
                    }
                }

                $proposedManagers = [];
                $userInstances = [];

                foreach ($processedUsers as $rowIndex => $user) {
                    $row = $rows[$rowIndex];
                    $managerCol = trim($this->getRowVal($row, $canonicalMap, 'reporting_manager') ?? '');

                    $resolvedManagerId = null;
                    if (!empty($managerCol)) {
                        if (preg_match('/\(([^)]+)\)/', $managerCol, $matches)) {
                            $extractedCode = trim($matches[1]);
                            $stdCode = $this->matchingService->standardizeEmployeeId($extractedCode);
                            $managerUser = User::where('employee_id', $stdCode)->orWhere('employee_id', $extractedCode)->first();
                            
                            if ($managerUser) {
                                $resolvedManagerId = $managerUser->id;
                            } else {
                                $managerCodeInt = (int)$extractedCode;
                                if (isset($userLookup[$managerCodeInt])) {
                                    $resolvedManagerId = $userLookup[$managerCodeInt];
                                }
                            }
                        }

                        if (!$resolvedManagerId) {
                            $stdCode = $this->matchingService->standardizeEmployeeId($managerCol);
                            $managerUser = User::where('employee_id', $stdCode)->orWhere('employee_id', $managerCol)->first();
                            if ($managerUser) {
                                $resolvedManagerId = $managerUser->id;
                            }
                        }

                        if (!$resolvedManagerId) {
                            $managerUser = User::whereRaw('LOWER(name) = ?', [strtolower(trim($managerCol))])->first();
                            if ($managerUser) {
                                $resolvedManagerId = $managerUser->id;
                            }
                        }
                    }

                    $proposedManagers[$user->id] = $resolvedManagerId;
                    $userInstances[$user->id] = $user;
                }

                // Loop Detection: Check for multi-level circular reporting cycles
                foreach ($proposedManagers as $userId => $managerId) {
                    if ($managerId === null) {
                        continue;
                    }

                    if ($userId === $managerId) {
                        $userObj = $userInstances[$userId];
                        throw new \Exception("Circular reporting detected: Employee {$userObj->name} ({$userObj->employee_id}) cannot report to themselves.");
                    }

                    $visited = [$userId => true];
                    $current = $managerId;

                    while ($current !== null) {
                        if (isset($visited[$current])) {
                            $userObj = User::find($userId) ?? $userInstances[$userId];
                            $mgrObj = User::find($managerId) ?? $userInstances[$managerId];
                            throw new \Exception("Circular reporting loop detected involving employee {$userObj->name} ({$userObj->employee_id}) and manager {$mgrObj->name} ({$mgrObj->employee_id}).");
                        }
                        $visited[$current] = true;

                        if (array_key_exists($current, $proposedManagers)) {
                            $current = $proposedManagers[$current];
                        } else {
                            $current = User::where('id', $current)->value('manager_id');
                        }
                    }
                }

                // Write manager assignments to database
                foreach ($proposedManagers as $userId => $managerId) {
                    $user = $userInstances[$userId];
                    $user->manager_id = $managerId;
                    $user->save();

                    if ($managerId) {
                        $mgr = User::find($managerId);
                        if ($mgr && $mgr->role === 'employee') {
                            $mgr->role = 'manager';
                            $mgr->save();
                        }
                    }
                }
            } else {
                // UPDATE MODE: Process row updates
                foreach ($rows as $rowIndex => $row) {
                    if ($rowIndex === 1) {
                        continue;
                    }

                    $nonEmptyCells = array_filter($row, fn($val) => $val !== null && trim((string)$val) !== '');
                    if (empty($nonEmptyCells)) {
                        continue;
                    }

                    $rowsProcessed++;

                    $externalCode = $this->getRowVal($row, $canonicalMap, 'employee_code');
                    $standardizedId = $this->matchingService->standardizeEmployeeId($externalCode);

                    if (empty($externalCode)) {
                        throw new \Exception("Validation Failure at Row {$rowIndex}: Employee Code is missing.");
                    }

                    // Resolve user
                    $user = null;

                    if (isset($approvedMappings[$externalCode])) {
                        $mappedVal = $approvedMappings[$externalCode];
                        if ($mappedVal === 'skip' || empty($mappedVal)) {
                            $skippedCount++;
                            continue; // Skip this row during import
                        }
                        $user = User::find($mappedVal);
                    }

                    if (!$user) {
                        $mapping = EmployeeExternalIdentifier::where('source', $profile->source_system)
                            ->where('external_identifier', $externalCode)
                            ->first();
                        if ($mapping) {
                            $user = User::find($mapping->user_id);
                        }
                    }

                    if (!$user) {
                        $user = User::where('employee_id', $standardizedId)
                            ->orWhere('employee_id', $externalCode)
                            ->first();
                    }

                    if (!$user) {
                        throw new \Exception("Database Integrity Error: Row {$rowIndex} matches no employee. All ambiguity must be resolved before import confirmation.");
                    }

                    $hasChange = false;
                    $payrollProfile = PayrollProfile::firstOrCreate(['user_id' => $user->id]);
                    $leaveBalance = LeaveBalance::firstOrCreate(['user_id' => $user->id]);

                    // Base Salary Updates
                    if ($hasSalaryCat) {
                        $salaryVal = $this->getRowVal($row, $canonicalMap, 'base_salary');
                        $effectiveVal = $this->getRowVal($row, $canonicalMap, 'salary_effective_date');
                        $enabledVal = $this->getRowVal($row, $canonicalMap, 'payroll_enabled');

                        $newSalary = ($salaryVal !== null && trim((string)$salaryVal) !== '') ? $this->matchingService->parseNumeric($salaryVal) : null;
                        $newEffective = ($effectiveVal !== null && trim((string)$effectiveVal) !== '') ? $this->matchingService->parseDate($effectiveVal) : null;
                        $newEnabled = ($enabledVal !== null && trim((string)$enabledVal) !== '') ? $this->parseBoolean($enabledVal) : null;

                        $salaryUpdate = [
                            'last_imported_at' => now(),
                            'imported_by_id' => $runByUserId,
                            'import_source' => 'Imported',
                        ];
                        $salaryChanged = false;

                        if ($newSalary !== null) {
                            if ($payrollProfile->base_salary === null || (float)$payrollProfile->base_salary !== (float)$newSalary) {
                                $salaryUpdate['base_salary'] = $newSalary;
                                $salaryChanged = true;
                            }
                        }
                        if ($newEffective !== null) {
                            $currEffective = $payrollProfile->salary_effective_date ? $payrollProfile->salary_effective_date->format('Y-m-d') : null;
                            if ($currEffective !== $newEffective) {
                                $salaryUpdate['salary_effective_date'] = $newEffective;
                                $salaryChanged = true;
                            }
                        }
                        if ($newEnabled !== null) {
                            if ((bool)$payrollProfile->payroll_enabled !== (bool)$newEnabled) {
                                $salaryUpdate['payroll_enabled'] = $newEnabled;
                                $hasChange = true;
                            }
                        }

                        if ($salaryChanged) {
                            $payrollProfile->update($salaryUpdate);
                            $payrollProfile->recordSalaryRevision(
                                $newSalary ?? $payrollProfile->base_salary ?? 0.00,
                                $newEffective ?? ($payrollProfile->salary_effective_date ? $payrollProfile->salary_effective_date->format('Y-m-d') : now()->format('Y-m-d')),
                                'Salary updated via selective excel import',
                                $runByUserId,
                                'Imported'
                            );
                            $hasChange = true;
                        } elseif (isset($salaryUpdate['payroll_enabled'])) {
                            $payrollProfile->update($salaryUpdate);
                        }
                    }

                    // Leave Balance Updates
                    if ($hasLeavesCat) {
                        $leaveKeysMap = [
                            'planned_leave' => 'planned_leave',
                            'unplanned_leave' => 'unplanned_leave',
                            'paternity_leave' => 'paternity_leave',
                            'maternity_leave' => 'maternity_leave',
                            'compensatory_leave' => 'compensatory_leave',
                            'pending_leave' => 'pending_leave',
                            'utilized_leave' => 'utilized_leave',
                            'carry_forward' => 'carry_forward',
                            'remaining_leave' => 'remaining_leave',
                        ];

                        $leaveUpdate = [
                            'last_imported_at' => now(),
                            'imported_by_id' => $runByUserId,
                            'import_source' => 'Imported',
                        ];
                        $leavesChanged = false;
                        $oldRemaining = (float)($leaveBalance->remaining_leave ?? 0.00);

                        foreach ($leaveKeysMap as $dbField => $canonicalKey) {
                            if (isset($canonicalMap[$canonicalKey])) {
                                $val = $this->getRowVal($row, $canonicalMap, $canonicalKey);
                                if ($val !== null && trim((string)$val) !== '') {
                                    $parsedVal = $this->matchingService->parseNumeric($val);
                                    if ($parsedVal !== null) {
                                        $currVal = $leaveBalance->$dbField;
                                        if ($currVal === null || (float)$currVal !== (float)$parsedVal) {
                                            $leaveUpdate[$dbField] = $parsedVal;
                                            $leavesChanged = true;
                                        }
                                    }
                                }
                            }
                        }

                        if ($leavesChanged) {
                            $leaveBalance->update($leaveUpdate);
                            $hasChange = true;

                            if (isset($leaveUpdate['remaining_leave'])) {
                                $newRemaining = (float)$leaveUpdate['remaining_leave'];
                                $diff = $newRemaining - $oldRemaining;

                                if ((float)$diff !== 0.0) {
                                    $user->leave_balance = $newRemaining;
                                    $user->save();

                                    LeaveLedgerEntry::create([
                                        'user_id' => $user->id,
                                        'amount' => $diff,
                                        'type' => 'adjustment',
                                        'description' => "Remaining Leave adjusted via Excel import: {$oldRemaining} -> {$newRemaining}",
                                    ]);
                                }
                            }
                        }
                    }

                    if ($hasChange) {
                        $updatedCount++;
                    } else {
                        $skippedCount++;
                    }
                }
            }

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $endTime = microtime(true);
        $durationSeconds = round($endTime - $startTime, 2);

        return [
            'rows_processed' => $rowsProcessed,
            'created' => $createdCount,
            'updated' => $updatedCount,
            'skipped' => $skippedCount,
            'duration_seconds' => $durationSeconds,
            'errors' => $errors,
        ];
    }

    /**
     * Helper to read value from row matching the canonical map.
     */
    private function getRowVal(array $row, array $canonicalMap, string $canonicalKey): ?string
    {
        $letter = $canonicalMap[$canonicalKey] ?? null;
        return $letter ? ($row[$letter] ?? null) : null;
    }

    /**
     * Parse row columns into standard employee profile format.
     */
    private function getProfileDataMap(array $row, array $canonicalMap): array
    {
        return [
            'father_name' => $row[$canonicalMap['father_name'] ?? ''] ?? null,
            'mother_name' => $row[$canonicalMap['mother_name'] ?? ''] ?? null,
            'gender' => $row[$canonicalMap['gender'] ?? ''] ?? null,
            'date_of_birth' => $this->matchingService->parseDate($row[$canonicalMap['date_of_birth'] ?? ''] ?? null),
            'marital_status' => $row[$canonicalMap['marital_status'] ?? ''] ?? null,
            'date_of_marriage' => $this->matchingService->parseDate($row[$canonicalMap['date_of_marriage'] ?? ''] ?? null),
            'nationality' => $row[$canonicalMap['nationality'] ?? ''] ?? null,
            'blood_group' => $row[$canonicalMap['blood_group'] ?? ''] ?? null,
            'personal_email' => $row[$canonicalMap['personal_email'] ?? ''] ?? null,
            'mobile_no' => $this->matchingService->cleanPhoneNumber($row[$canonicalMap['mobile_no'] ?? ''] ?? null),
            'pf_uan' => $row[$canonicalMap['pf_uan'] ?? ''] ?? null,
            'passport_no' => $row[$canonicalMap['passport_no'] ?? ''] ?? null,
            'aadhar_card' => $row[$canonicalMap['aadhar_card'] ?? ''] ?? null,
            'pan' => $row[$canonicalMap['pan'] ?? ''] ?? null,
            'pf_no' => $row[$canonicalMap['pf_no'] ?? ''] ?? null,
            'esi_number' => $row[$canonicalMap['esi_number'] ?? ''] ?? null,
            'date_of_gratuity' => $this->matchingService->parseDate($row[$canonicalMap['date_of_gratuity'] ?? ''] ?? null),
            'payroll_type' => $row[$canonicalMap['payroll_type'] ?? ''] ?? null,
            'contract_end_date' => $this->matchingService->parseDate($row[$canonicalMap['contract_end_date'] ?? ''] ?? null),
            'office_landline' => $this->matchingService->cleanPhoneNumber($row[$canonicalMap['office_landline'] ?? ''] ?? null),
            'leave_rule' => $row[$canonicalMap['leave_rule'] ?? ''] ?? null,
            'shift' => $row[$canonicalMap['shift'] ?? ''] ?? null,
            'designation' => $row[$canonicalMap['designation'] ?? ''] ?? null,
            'grade' => $row[$canonicalMap['grade'] ?? ''] ?? null,
            'employee_type' => $row[$canonicalMap['employee_type'] ?? ''] ?? null,
            'company' => $row[$canonicalMap['company'] ?? ''] ?? null,
            'location' => $row[$canonicalMap['location'] ?? ''] ?? null,
            'biometric_id' => $row[$canonicalMap['biometric_id'] ?? ''] ?? null,
            'hiring_source' => $row[$canonicalMap['hiring_source'] ?? ''] ?? null,
            'source_of_verification' => $row[$canonicalMap['source_of_verification'] ?? ''] ?? null,
            
            // Address fields
            'current_address1' => $row[$canonicalMap['current_address1'] ?? ''] ?? null,
            'current_address2' => $row[$canonicalMap['current_address2'] ?? ''] ?? null,
            'current_country' => $row[$canonicalMap['current_country'] ?? ''] ?? null,
            'current_state' => $row[$canonicalMap['current_state'] ?? ''] ?? null,
            'current_city' => $row[$canonicalMap['current_city'] ?? ''] ?? null,
            'current_zip' => $row[$canonicalMap['current_zip'] ?? ''] ?? null,

            'permanent_address1' => $row[$canonicalMap['permanent_address1'] ?? ''] ?? null,
            'permanent_address2' => $row[$canonicalMap['permanent_address2'] ?? ''] ?? null,
            'permanent_country' => $row[$canonicalMap['permanent_country'] ?? ''] ?? null,
            'permanent_state' => $row[$canonicalMap['permanent_state'] ?? ''] ?? null,
            'permanent_city' => $row[$canonicalMap['permanent_city'] ?? ''] ?? null,
            'permanent_zip' => $row[$canonicalMap['permanent_zip'] ?? ''] ?? null,

            'same_as_current_address' => $this->parseBoolean($row[$canonicalMap['same_as_current_address'] ?? ''] ?? null),
            'payment_type' => $row[$canonicalMap['payment_type'] ?? ''] ?? null,
            'bank_name' => $row[$canonicalMap['bank_name'] ?? ''] ?? null,
            'account_holder_name' => $row[$canonicalMap['account_holder_name'] ?? ''] ?? null,
            'account_no' => $row[$canonicalMap['account_no'] ?? ''] ?? null,
            'ifsc_code' => $row[$canonicalMap['ifsc_code'] ?? ''] ?? null,

            // Emergency contacts
            'emergency_name' => $row[$canonicalMap['emergency_name'] ?? ''] ?? null,
            'emergency_relationship' => $row[$canonicalMap['emergency_relationship'] ?? ''] ?? null,
            'emergency_address' => $row[$canonicalMap['emergency_address'] ?? ''] ?? null,
            'emergency_email' => $row[$canonicalMap['emergency_email'] ?? ''] ?? null,
            'emergency_mobile' => $this->matchingService->cleanPhoneNumber($row[$canonicalMap['emergency_mobile'] ?? ''] ?? null),

            // Education & Experience
            'degree_name' => $row[$canonicalMap['degree_name'] ?? ''] ?? null,
            'institution_name' => $row[$canonicalMap['institution_name'] ?? ''] ?? null,
            'passing_year' => $row[$canonicalMap['passing_year'] ?? ''] ?? null,
            'percentage' => $row[$canonicalMap['percentage'] ?? ''] ?? null,
            'previous_company_name' => $row[$canonicalMap['previous_company_name'] ?? ''] ?? null,
            'previous_job_title' => $row[$canonicalMap['previous_job_title'] ?? ''] ?? null,
            'previous_from_date' => $this->matchingService->parseDate($row[$canonicalMap['previous_from_date'] ?? ''] ?? null),
            'previous_to_date' => $this->matchingService->parseDate($row[$canonicalMap['previous_to_date'] ?? ''] ?? null),
            'state_name' => $row[$canonicalMap['state_name'] ?? ''] ?? null,
            'probation_period' => $row[$canonicalMap['probation_period'] ?? ''] ?? null,
            'probation_confirm_date' => $this->matchingService->parseDate($row[$canonicalMap['probation_confirm_date'] ?? ''] ?? null),
            'separation_date' => $this->matchingService->parseDate($row[$canonicalMap['separation_date'] ?? ''] ?? null),
            'last_working_day' => $this->matchingService->parseDate($row[$canonicalMap['last_working_day'] ?? ''] ?? null),
            'previous_year_experience' => $this->trimVal($row[$canonicalMap['previous_year_experience'] ?? ''] ?? null),
            'years_completed' => $this->trimVal($row[$canonicalMap['years_completed'] ?? ''] ?? null),
            'overall_year_experience' => $this->trimVal($row[$canonicalMap['overall_year_experience'] ?? ''] ?? null),
            'city_type' => $row[$canonicalMap['city_type'] ?? ''] ?? null,
            'notice_days' => $this->parseInteger($row[$canonicalMap['notice_days'] ?? ''] ?? null),
            'joining_date' => $this->matchingService->parseDate($row[$canonicalMap['joining_date'] ?? ''] ?? null),
        ];
    }

    private function parseBoolean($value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }
        $value = strtolower(trim($value));
        return in_array($value, ['yes', '1', 'true', 'y', 'enabled', 'active']);
    }

    private function parseInteger($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $cleaned = preg_replace('/[^\d-]/', '', $value);
        return is_numeric($cleaned) ? (int)$cleaned : null;
    }

    private function trimVal($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        return trim($value);
    }
}