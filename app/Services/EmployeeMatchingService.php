<?php

namespace App\Services;

use App\Models\User;
use App\Models\Department;
use App\Models\EmployeeExternalIdentifier;
use Carbon\Carbon;
use App\Models\ImportProfile;

class EmployeeMatchingService
{
    protected float $autoMatchThreshold = 80.0;

    /**
     * Resolve secondary/profile fields using a fallback mapping list.
     */
    public function getFallbackCanonicalKey(string $headerName): ?string
    {
        $normalized = strtolower(str_replace([' ', '_', '-', '.', '/'], '', trim($headerName)));

        $fallbacks = [
            'father_name' => ['father name', "father's name", 'fathername'],
            'mother_name' => ['mother name', "mother's name", 'mothername'],
            'gender' => ['gender', 'sex'],
            'date_of_birth' => ['date of birth', 'dob', 'birth date', 'birth_date'],
            'marital_status' => ['marital status', 'marital_status'],
            'date_of_marriage' => ['date of marriage', 'dom', 'marriage_date'],
            'nationality' => ['nationality'],
            'blood_group' => ['blood group', 'blood grouping'],
            'personal_email' => ['personal email id', 'personal email'],
            'pf_uan' => ['pf uan', 'uan'],
            'passport_no' => ['passport no.', 'passport number', 'passport'],
            'aadhar_card' => ['aadhar card', 'aadhar no', 'aadhar'],
            'pan' => ['pan', 'pan card', 'pan no'],
            'pf_no' => ['pf no', 'pf number'],
            'esi_number' => ['esi number', 'esi no', 'esi'],
            'date_of_gratuity' => ['date of gratuity'],
            'payroll_type' => ['payroll type'],
            'contract_end_date' => ['contract end date'],
            'office_landline' => ['office landline number', 'office landline'],
            'leave_rule' => ['leave rule'],
            'shift' => ['shift'],
            'designation' => ['designation'],
            'grade' => ['grade'],
            'employee_type' => ['employee type'],
            'company' => ['company'],
            'location' => ['location'],
            'biometric_id' => ['biometric id'],
            'hiring_source' => ['hiring source'],
            'source_of_verification' => ['source of verification'],
            'notice_days' => ['notice days'],
            'joining_date' => ['joining date', 'date of joining'],
            'city_type' => ['city type'],
            
            // Address columns
            'current_address1' => ['current address1', 'address1'],
            'current_address2' => ['current address2', 'address2'],
            'current_country' => ['current country', 'country'],
            'current_state' => ['current state', 'state'],
            'current_city' => ['current city', 'city'],
            'current_zip' => ['current zip', 'zip'],
            
            'permanent_address1' => ['permanent address1'],
            'permanent_address2' => ['permanent address2'],
            'permanent_country' => ['permanent country', 'country.1', 'country_1'],
            'permanent_state' => ['permanent state', 'state.1', 'state_1'],
            'permanent_city' => ['permanent city', 'city.1', 'city_1'],
            'permanent_zip' => ['permanent zip', 'zip.1', 'zip_1'],
            'same_as_current_address' => ['same as current address'],

            // Bank columns
            'payment_type' => ['payment type'],
            'bank_name' => ['bank name'],
            'account_holder_name' => ['account holder name'],
            'account_no' => ['account no', 'account number'],
            'ifsc_code' => ['ifsc code', 'ifsc'],

            // Emergency columns
            'emergency_name' => ['name'],
            'emergency_relationship' => ['relationship'],
            'emergency_address' => ['address'],
            'emergency_email' => ['email'],
            'emergency_mobile' => ['mobile no..1', 'emergency mobile', 'mobile'],

            // Education columns
            'degree_name' => ['diploma/degree name'],
            'institution_name' => ['institution name'],
            'passing_year' => ['passing year'],
            'percentage' => ['percentage'],

            // Experience columns
            'previous_company_name' => ['previous company name'],
            'previous_job_title' => ['job title'],
            'previous_from_date' => ['from date'],
            'previous_to_date' => ['to date'],
            'state_name' => ['state name'],
            'probation_period' => ['probation period'],
            'probation_confirm_date' => ['probation confirm_date'],
            'separation_date' => ['seprate date'],
            'previous_year_experience' => ['previous year_experience'],
            'years_completed' => ['number of_year_completed'],
            'overall_year_experience' => ['overall year_experience'],
            
            // Payroll & Leave fields fallback
            'base_salary' => ['base salary', 'salary', 'emp salary', 'employee salary'],
            'salary_effective_date' => ['salary effective date', 'effective date', 'salary_effective_date'],
            'payroll_enabled' => ['payroll enabled', 'enabled', 'payroll_enabled'],
            'planned_leave' => ['planned leave', 'planned', 'plannedleave'],
            'unplanned_leave' => ['unplanned leave', 'unplanned', 'unplannedleave'],
            'paternity_leave' => ['paternity leave', 'paternity', 'paternityleave'],
            'maternity_leave' => ['maternity leave', 'maternity', 'maternityleave'],
            'compensatory_leave' => ['compensatory leave', 'compensatory', 'compensatoryleave'],
            'pending_leave' => ['pending leave', 'pending', 'pendingleave', 'total pending'],
            'utilized_leave' => ['utilized leave', 'utilized', 'utilizedleave', 'utilized / applied leave', 'utilized/applied leave'],
            'carry_forward' => ['carry forward', 'carryforward', 'carry_forward', 'total carry forward'],
            'remaining_leave' => ['remaining leave', 'remaining', 'remainingleave', 'total remaining'],
        ];

        foreach ($fallbacks as $canonical => $aliases) {
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
     * Parse numeric values consistently.
     */
    public function parseNumeric(?string $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        $cleaned = preg_replace('/[^0-9.-]/', '', $value);
        return is_numeric($cleaned) ? (float)$cleaned : null;
    }

    /**
     * Set the threshold for auto-matching.
     */
    public function setAutoMatchThreshold(float $threshold): void
    {
        $this->autoMatchThreshold = $threshold;
    }

    /**
     * Clean and standardize employee ID formatting.
     */
    public function standardizeEmployeeId(?string $code): ?string
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

    /**
     * Clean phone number digits.
     */
    public function cleanPhoneNumber(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $value = trim((string)$value);
        if (str_ends_with($value, '.0')) {
            $value = substr($value, 0, -2);
        }
        $cleaned = preg_replace('/[^\d+]/', '', $value);
        return $cleaned !== '' ? $cleaned : null;
    }

    /**
     * Parse date inputs consistently.
     */
    public function parseDate(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $value = trim($value);
        if (is_numeric($value)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$value)->format('Y-m-d');
            } catch (\Exception $e) {
                // Ignore
            }
        }
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Normalize a string consistently: Unicode Form C, lowercase, trim, collapses spaces, strips prefix honorifics Mr/Mrs.
     */
    public function normalizeString(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (class_exists('\Normalizer')) {
            $value = \Normalizer::normalize($value, \Normalizer::FORM_C);
        }
        $value = trim($value);
        $value = preg_replace('/^(mr\.|mrs\.|ms\.|dr\.|mr|mrs|ms|dr)\s+/i', '', $value);
        $value = preg_replace('/\s+/', ' ', $value);
        return mb_strtolower($value, 'UTF-8');
    }

    /**
     * Normalize and find department ID dynamically.
     */
    public function findDepartmentId(?string $deptName): ?int
    {
        if (empty($deptName)) {
            return null;
        }
        $norm = strtolower(str_replace([' ', '_', '-', '/', '&', 's'], '', trim($deptName)));

        // Try exact name match
        $id = Department::whereRaw('LOWER(name) = ?', [strtolower(trim($deptName))])->value('id');
        if ($id) {
            return $id;
        }

        // Try code match
        $id = Department::whereRaw('LOWER(code) = ?', [strtolower(trim($deptName))])->value('id');
        if ($id) {
            return $id;
        }

        // Try fuzzy comparison stripping plurals, spaces, and ampersands
        $depts = Department::all();
        foreach ($depts as $dept) {
            $dNorm = strtolower(str_replace([' ', '_', '-', '/', '&', 's'], '', trim($dept->name)));
            $cNorm = strtolower(str_replace([' ', '_', '-', '/', '&', 's'], '', trim($dept->code)));
            if ($norm === $dNorm || $norm === $cNorm) {
                return $dept->id;
            }
        }
        return null;
    }

    /**
     * Find candidates for a given row and compute match scores.
     * Returns an array with the matched user (if above auto-match threshold) and ranked candidates.
     */
    public function matchRow(array $rowData, string $sourceSystem, ?ImportProfile $profile = null): array
    {
        // 1. Extract values from row data
        $rawCode = $rowData['employee_code'] ?? null;
        $email = $rowData['official_email'] ?? null;
        $name = $rowData['full_name'] ?? null;
        $deptName = $rowData['department'] ?? null;
        $designation = $rowData['designation'] ?? null;
        $joiningDateRaw = $rowData['joining_date'] ?? null;
        $mobileRaw = $rowData['mobile_no'] ?? null;

        $standardizedCode = $this->standardizeEmployeeId($rawCode);
        $cleanEmail = $email ? mb_strtolower(trim($email), 'UTF-8') : null;
        if ($cleanEmail && class_exists('\Normalizer')) {
            $cleanEmail = \Normalizer::normalize($cleanEmail, \Normalizer::FORM_C);
        }
        $cleanName = $this->normalizeString($name);
        $cleanMobile = $this->cleanPhoneNumber($mobileRaw);
        $joiningDate = $this->parseDate($joiningDateRaw);

        // Resolve Department ID with normalization
        $deptId = $this->findDepartmentId($deptName);

        // 2. Fetch Potential Candidates
        $candidateIds = collect();

        // Match on Employee Code
        if ($standardizedCode) {
            $ids = User::where('employee_id', $standardizedCode)
                ->orWhere('employee_id', $rawCode)
                ->pluck('id');
            $candidateIds = $candidateIds->merge($ids);
        }

        // Match on Email
        if ($cleanEmail) {
            $ids = User::whereRaw('LOWER(email) = ?', [$cleanEmail])->pluck('id');
            $candidateIds = $candidateIds->merge($ids);
        }

        // Match on Name (Exact or term lookup)
        if ($cleanName) {
            $ids = User::whereRaw('LOWER(name) = ?', [$cleanName])->pluck('id');
            $candidateIds = $candidateIds->merge($ids);

            $terms = array_filter(explode(' ', $cleanName), fn($t) => strlen($t) > 2);
            if (count($terms) > 0) {
                $query = User::query();
                foreach ($terms as $term) {
                    $query->orWhere('name', 'like', "%{$term}%");
                }
                $ids = $query->limit(15)->pluck('id');
                $candidateIds = $candidateIds->merge($ids);
            }
        }

        // Match on Mobile Number
        if ($cleanMobile) {
            $ids = User::where('phone', $cleanMobile)->pluck('id');
            $candidateIds = $candidateIds->merge($ids);

            $idsFromProfile = User::whereHas('employeeProfile', function ($q) use ($cleanMobile) {
                $q->where('mobile_no', $cleanMobile);
            })->pluck('id');
            $candidateIds = $candidateIds->merge($idsFromProfile);
        }

        // Match on External Identifier Mappings
        if ($rawCode) {
            $ids = EmployeeExternalIdentifier::where('source', $sourceSystem)
                ->where('external_identifier', $rawCode)
                ->where('is_active', true)
                ->pluck('user_id');
            $candidateIds = $candidateIds->merge($ids);
        }

        // Get unique candidate IDs
        $uniqueIds = $candidateIds->unique()->filter()->values();

        // Fallback scan by Name + Department if no candidates matched
        if ($uniqueIds->isEmpty() && $cleanName) {
            $query = User::query();
            if ($deptId) {
                $query->where('department_id', $deptId);
            }
            $ids = $query->whereRaw('LOWER(name) LIKE ?', ["%{$cleanName}%"])
                ->limit(5)
                ->pluck('id');
            $uniqueIds = $ids->unique()->filter()->values();
        }

        // Load configured priority weights
        $weights = $profile && $profile->matching_weights ? $profile->matching_weights : [
            'employee_id' => 60,
            'email' => 50,
            'external_identifier' => 40,
            'name' => 25,
            'department' => 15,
            'designation' => 10,
            'joining_date' => 10,
            'mobile' => 5,
        ];

        // 3. Score Each Candidate
        $candidates = [];
        $users = User::with(['employeeProfile', 'department'])->whereIn('id', $uniqueIds)->get();

        foreach ($users as $user) {
            $score = 0;
            $breakdown = [];

            // A. Employee ID
            if ($standardizedCode && ($user->employee_id === $standardizedCode || $user->employee_id === $rawCode)) {
                $w = $weights['employee_id'] ?? 60;
                $score += $w;
                $breakdown[] = "AMS Employee ID Match (+{$w})";
            }

            // B. Official Email
            if ($cleanEmail && strtolower($user->email) === $cleanEmail) {
                $w = $weights['email'] ?? 50;
                $score += $w;
                $breakdown[] = "Official Email Match (+{$w})";
            }

            // C. External Identifier Mappings
            if ($rawCode) {
                $hasMapping = EmployeeExternalIdentifier::where('source', $sourceSystem)
                    ->where('external_identifier', $rawCode)
                    ->where('user_id', $user->id)
                    ->where('is_active', true)
                    ->exists();
                if ($hasMapping) {
                    $w = $weights['external_identifier'] ?? 40;
                    $score += $w;
                    $breakdown[] = "External Identifier Mapping Match (+{$w})";
                }
            }

            // D. Full Name (Normalized comparison)
            if ($cleanName && $this->normalizeString($user->name) === $cleanName) {
                $w = $weights['name'] ?? 25;
                $score += $w;
                $breakdown[] = "Full Name Match (+{$w})";
            }

            // E. Department
            if ($deptId && $user->department_id === $deptId) {
                $w = $weights['department'] ?? 15;
                $score += $w;
                $breakdown[] = "Department Match (+{$w})";
            }

            // F. Designation
            if ($designation && $user->employeeProfile && !empty($user->employeeProfile->designation)) {
                if (strtolower(trim($user->employeeProfile->designation)) === strtolower(trim($designation))) {
                    $w = $weights['designation'] ?? 10;
                    $score += $w;
                    $breakdown[] = "Designation Match (+{$w})";
                }
            }

            // G. Joining Date
            if ($joiningDate && $user->joining_date) {
                $currJoining = $user->joining_date instanceof Carbon ? $user->joining_date->format('Y-m-d') : Carbon::parse($user->joining_date)->format('Y-m-d');
                if ($currJoining === $joiningDate) {
                    $w = $weights['joining_date'] ?? 10;
                    $score += $w;
                    $breakdown[] = "Joining Date Match (+{$w})";
                }
            }

            // H. Mobile Number
            $userMobile = $this->cleanPhoneNumber($user->phone);
            if (!$userMobile && $user->employeeProfile) {
                $userMobile = $this->cleanPhoneNumber($user->employeeProfile->mobile_no);
            }
            if ($cleanMobile && $userMobile === $cleanMobile) {
                $w = $weights['mobile'] ?? 5;
                $score += $w;
                $breakdown[] = "Mobile Number Match (+{$w})";
            }

            $candidates[] = [
                'user' => $user,
                'score' => $score,
                'breakdown' => $breakdown,
            ];
        }

        // Sort candidates by match score descending
        usort($candidates, fn($a, $b) => $b['score'] <=> $a['score']);

        // 4. Determine Auto Match or Manual Review
        $matchedUser = null;
        $matchedScore = 0;
        $matchedBreakdown = [];

        if (count($candidates) > 0) {
            $best = $candidates[0];
            if ($best['score'] >= $this->autoMatchThreshold) {
                $matchedUser = $best['user'];
                $matchedScore = $best['score'];
                $matchedBreakdown = $best['breakdown'];
            }
        }

        return [
            'matched_user' => $matchedUser,
            'score' => $matchedScore,
            'breakdown' => $matchedBreakdown,
            'candidates' => $candidates,
            'threshold' => $this->autoMatchThreshold,
        ];
    }
}
