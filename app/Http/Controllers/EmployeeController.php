<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use App\Services\EmployeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    public function __construct(
        protected EmployeeService $employeeService
    ) {
    }

    // =========================================================
    // Index — list all employees
    // =========================================================

    public function index(Request $request): View
    {
        $currentUser = auth()->user();
        if ($currentUser->role === 'employee') {
            abort(403, 'Unauthorized action.');
        }

        $query = User::with(['department', 'manager', 'admin', 'employeeProfile']);

        if ($currentUser->role === 'manager') {
            $query->where('role', 'employee')
                ->where('manager_id', $currentUser->id);
        }

        // Search: Name, Email, Employee ID
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        // Filters
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        if ($request->filled('manager_id')) {
            $query->where('manager_id', $request->input('manager_id'));
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');

        $allowedSorts = ['name', 'employee_id', 'email', 'status', 'created_at', 'joining_date'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        } else {
            $query->latest();
        }

        // Pagination
        $employees = $query->paginate(15)->withQueryString();

        // For filter dropdowns
        $departments = Department::orderBy('name')->get();
        $managers = User::whereIn('role', ['manager', 'admin'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('employees.index', compact('employees', 'departments', 'managers'));
    }

    // =========================================================
    // Create — show blank form
    // =========================================================

    public function create(): View
    {
        $currentUser = auth()->user();
        if ($currentUser->role === 'employee') {
            abort(403, 'Unauthorized action.');
        }

        $departments = Department::orderBy('name')->get();

        // Active managers
        $managers = User::where('role', 'manager')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        // Active admins
        $admins = User::where('role', 'admin')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $suggestedEmployeeId = $this->generateEmployeeId();

        return view('employees.create', compact(
            'departments',
            'managers',
            'admins',
            'suggestedEmployeeId'
        ));
    }

    // =========================================================
    // Store — validate and save
    // =========================================================

    public function store(Request $request): RedirectResponse
    {
        $currentUser = auth()->user();
        if ($currentUser->role === 'employee') {
            abort(403, 'Unauthorized action.');
        }

        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'status' => ['required', 'string', 'in:active,inactive,resigned'],
            'phone' => ['nullable', 'string', 'max:20'],
            'joining_date' => ['nullable', 'date'],
            'department_id' => ['required', 'exists:departments,id'],
        ];

        if ($currentUser->role === 'admin') {
            $rules['role'] = ['required', 'string', 'in:admin,manager,employee'];
            $rules['manager_id'] = ['nullable', 'exists:users,id'];
            $rules['admin_id'] = ['nullable', 'exists:users,id'];
        } else { // manager
            // A manager can only create employees
            $rules['role'] = ['required', 'string', 'in:employee'];
            $rules['admin_id'] = ['nullable', 'exists:users,id'];
        }

        $profileRules = [
            'father_name' => ['nullable', 'string', 'max:255'],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'in:Male,Female,Other'],
            'date_of_birth' => ['nullable', 'date'],
            'marital_status' => ['nullable', 'string', 'max:100'],
            'date_of_marriage' => ['nullable', 'date'],
            'nationality' => ['nullable', 'string', 'max:255'],
            'blood_group' => ['nullable', 'string', 'max:20'],
            'personal_email' => ['nullable', 'email', 'max:255'],
            'mobile_no' => ['nullable', 'string', 'max:255'],
            'pf_uan' => ['nullable', 'string', 'max:255'],
            'passport_no' => ['nullable', 'string', 'max:255'],
            'aadhar_card' => ['nullable', 'string', 'max:255'],
            'pan' => ['nullable', 'string', 'max:255'],
            'pf_no' => ['nullable', 'string', 'max:255'],
            'esi_number' => ['nullable', 'string', 'max:255'],
            'date_of_gratuity' => ['nullable', 'date'],
            'payroll_type' => ['nullable', 'string', 'max:255'],
            'contract_end_date' => ['nullable', 'date'],
            'office_landline' => ['nullable', 'string', 'max:255'],
            'leave_rule' => ['nullable', 'string', 'max:255'],
            'shift' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'grade' => ['nullable', 'string', 'max:255'],
            'employee_type' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'biometric_id' => ['nullable', 'string', 'max:255'],
            'hiring_source' => ['nullable', 'string', 'max:255'],
            'source_of_verification' => ['nullable', 'string', 'max:255'],
            'city_type' => ['nullable', 'string', 'max:255'],
            'notice_days' => ['nullable', 'integer', 'min:0'],
            'state_name' => ['nullable', 'string', 'max:255'],
            'current_address1' => ['nullable', 'string', 'max:255'],
            'current_address2' => ['nullable', 'string', 'max:255'],
            'current_country' => ['nullable', 'string', 'max:255'],
            'current_state' => ['nullable', 'string', 'max:255'],
            'current_city' => ['nullable', 'string', 'max:255'],
            'current_zip' => ['nullable', 'string', 'max:255'],
            'same_as_current_address' => ['nullable', 'boolean'],
            'permanent_address1' => ['nullable', 'string', 'max:255'],
            'permanent_address2' => ['nullable', 'string', 'max:255'],
            'permanent_country' => ['nullable', 'string', 'max:255'],
            'permanent_state' => ['nullable', 'string', 'max:255'],
            'permanent_city' => ['nullable', 'string', 'max:255'],
            'permanent_zip' => ['nullable', 'string', 'max:255'],
            'payment_type' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_holder_name' => ['nullable', 'string', 'max:255'],
            'account_no' => ['nullable', 'string', 'max:255'],
            'ifsc_code' => ['nullable', 'string', 'max:255'],
            'emergency_name' => ['nullable', 'string', 'max:255'],
            'emergency_relationship' => ['nullable', 'string', 'max:255'],
            'emergency_address' => ['nullable', 'string', 'max:255'],
            'emergency_email' => ['nullable', 'email', 'max:255'],
            'emergency_mobile' => ['nullable', 'string', 'max:255'],
            'degree_name' => ['nullable', 'string', 'max:255'],
            'institution_name' => ['nullable', 'string', 'max:255'],
            'passing_year' => ['nullable', 'string', 'max:255'],
            'percentage' => ['nullable', 'string', 'max:255'],
            'previous_company_name' => ['nullable', 'string', 'max:255'],
            'previous_job_title' => ['nullable', 'string', 'max:255'],
            'previous_from_date' => ['nullable', 'date'],
            'previous_to_date' => ['nullable', 'date'],
            'probation_period' => ['nullable', 'string', 'max:255'],
            'probation_confirm_date' => ['nullable', 'date'],
            'separation_date' => ['nullable', 'date'],
            'last_working_day' => ['nullable', 'date'],
            'previous_year_experience' => ['nullable', 'string', 'max:255'],
            'years_completed' => ['nullable', 'string', 'max:255'],
            'overall_year_experience' => ['nullable', 'string', 'max:255'],
        ];

        $rules = array_merge($rules, $profileRules);

        $validated = $request->validate($rules);

        // Address copy logic for same_as_current_address
        if ($request->boolean('same_as_current_address')) {
            $validated['permanent_address1'] = $validated['current_address1'] ?? null;
            $validated['permanent_address2'] = $validated['current_address2'] ?? null;
            $validated['permanent_country'] = $validated['current_country'] ?? null;
            $validated['permanent_state'] = $validated['current_state'] ?? null;
            $validated['permanent_city'] = $validated['current_city'] ?? null;
            $validated['permanent_zip'] = $validated['current_zip'] ?? null;
            $validated['same_as_current_address'] = true;
        } else {
            $validated['same_as_current_address'] = false;
        }

        // Fill joining_date in profileData if set on User
        if (isset($validated['joining_date'])) {
            $validated['joining_date'] = $validated['joining_date'];
        }

        // Auto-generate employee ID
        $validated['employee_id'] = $this->generateEmployeeId();

        // Automatically assign the default employee password
        $tempPassword = config('employees.default_employee_password');
        if (empty($tempPassword)) {
            return back()->withErrors(['password' => 'The DEFAULT_EMPLOYEE_PASSWORD environment variable is not configured.'])->withInput();
        }
        $validated['password'] = $tempPassword;
        $validated['must_change_password'] = true;

        $targetRole = $currentUser->role === 'admin' ? $validated['role'] : 'employee';

        // Strict role hierarchy validation (prevents circular reporting)
        if ($targetRole === 'admin') {
            // Admin must always have admin_id = null and manager_id = null
            $validated['admin_id'] = null;
            $validated['manager_id'] = null;
        } elseif ($targetRole === 'manager') {
            // Managers may have an assigned Admin, but manager_id must be null
            $validated['manager_id'] = null;
            if (!empty($validated['admin_id'])) {
                $assignedAdmin = User::find($validated['admin_id']);
                if (!$assignedAdmin || $assignedAdmin->role !== 'admin') {
                    return back()->withErrors(['admin_id' => 'Managers can only be assigned to Admin users.'])->withInput();
                }
            }
        } else { // employee
            if ($currentUser->role === 'manager') {
                // Force manager to themselves
                $validated['manager_id'] = $currentUser->id;
            }

            if (!empty($validated['manager_id'])) {
                $assignedManager = User::find($validated['manager_id']);
                if (!$assignedManager || $assignedManager->role !== 'manager') {
                    return back()->withErrors(['manager_id' => 'Employees can only be assigned to Manager users.'])->withInput();
                }
            }

            if (!empty($validated['admin_id'])) {
                $assignedAdmin = User::find($validated['admin_id']);
                if (!$assignedAdmin || $assignedAdmin->role !== 'admin') {
                    return back()->withErrors(['admin_id' => 'Employees can only be assigned to Admin users.'])->withInput();
                }
            }
        }

        if ($currentUser->role === 'manager') {
            $validated['role'] = 'employee';
        }

        $employee = $this->employeeService->create($validated);

        return redirect()
            ->route('employees.index')
            ->with('success_provisioned', [
                'name' => $employee->name,
                'employee_id' => $employee->employee_id,
                'password' => $tempPassword,
            ]);
    }

    // =========================================================
    // Show — view an employee profile
    // =========================================================

    public function show(User $user): View
    {
        $currentUser = auth()->user();

        if ($currentUser->role === 'manager') {
            // Manager can view themselves or their direct reports
            if ($user->id !== $currentUser->id && ($user->role !== 'employee' || $user->manager_id !== $currentUser->id)) {
                abort(403, 'Unauthorized action.');
            }
        } elseif ($currentUser->role !== 'admin' && $currentUser->id !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        $user->load(['employeeProfile', 'department', 'manager', 'admin']);

        return view('employees.show', compact('user'));
    }

    // =========================================================
    // Private — Employee ID generator
    // =========================================================

    private function generateEmployeeId(): string
    {
        $latest = User::orderBy('id', 'desc')->value('employee_id');

        if ($latest && preg_match('/^EMP(\d+)$/', $latest, $matches)) {
            $number = (int) $matches[1] + 1;
        } else {
            $number = 1;
        }

        return 'EMP' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }

    public function edit(User $user)
    {
        $currentUser = auth()->user();
        if ($currentUser->role === 'employee') {
            abort(403, 'Unauthorized action.');
        }

        // Manager can only edit employees assigned to them
        if ($currentUser->role === 'manager') {
            if ($user->role !== 'employee' || $user->manager_id !== $currentUser->id) {
                abort(403, 'Unauthorized action.');
            }
        }

        $departments = Department::orderBy('name')->get();

        $managers = User::where('role', 'manager')
            ->where('status', 'active')
            ->where('id', '!=', $user->id)
            ->get();

        $admins = User::where('role', 'admin')
            ->where('status', 'active')
            ->where('id', '!=', $user->id)
            ->get();

        return view('employees.edit', compact('user', 'departments', 'managers', 'admins'));
    }

    public function update(Request $request, User $user)
    {
        $currentUser = auth()->user();
        if ($currentUser->role === 'employee') {
            abort(403, 'Unauthorized action.');
        }

        // Manager can only edit employees assigned to them
        if ($currentUser->role === 'manager') {
            if ($user->role !== 'employee' || $user->manager_id !== $currentUser->id) {
                abort(403, 'Unauthorized action.');
            }
        }

        $rules = [
            'employee_id' => ['required', 'string', 'max:50', Rule::unique('users')->ignore($user->id)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
            'joining_date' => ['nullable', 'date'],
            'department_id' => ['required', 'exists:departments,id'],
            'status' => ['required', 'in:active,inactive,resigned'],
        ];

        if ($currentUser->role === 'admin') {
            $rules['role'] = ['required', 'in:admin,manager,employee'];
            $rules['manager_id'] = ['nullable', 'exists:users,id'];
            $rules['admin_id'] = ['nullable', 'exists:users,id'];
        } else {
            // Manager cannot change role
            $rules['role'] = ['required', 'in:employee'];
            $rules['admin_id'] = ['nullable', 'exists:users,id'];
        }

        $profileRules = [
            'father_name' => ['nullable', 'string', 'max:255'],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'in:Male,Female,Other'],
            'date_of_birth' => ['nullable', 'date'],
            'marital_status' => ['nullable', 'string', 'max:100'],
            'date_of_marriage' => ['nullable', 'date'],
            'nationality' => ['nullable', 'string', 'max:255'],
            'blood_group' => ['nullable', 'string', 'max:20'],
            'personal_email' => ['nullable', 'email', 'max:255'],
            'mobile_no' => ['nullable', 'string', 'max:255'],
            'pf_uan' => ['nullable', 'string', 'max:255'],
            'passport_no' => ['nullable', 'string', 'max:255'],
            'aadhar_card' => ['nullable', 'string', 'max:255'],
            'pan' => ['nullable', 'string', 'max:255'],
            'pf_no' => ['nullable', 'string', 'max:255'],
            'esi_number' => ['nullable', 'string', 'max:255'],
            'date_of_gratuity' => ['nullable', 'date'],
            'payroll_type' => ['nullable', 'string', 'max:255'],
            'contract_end_date' => ['nullable', 'date'],
            'office_landline' => ['nullable', 'string', 'max:255'],
            'leave_rule' => ['nullable', 'string', 'max:255'],
            'shift' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'grade' => ['nullable', 'string', 'max:255'],
            'employee_type' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'biometric_id' => ['nullable', 'string', 'max:255'],
            'hiring_source' => ['nullable', 'string', 'max:255'],
            'source_of_verification' => ['nullable', 'string', 'max:255'],
            'city_type' => ['nullable', 'string', 'max:255'],
            'notice_days' => ['nullable', 'integer', 'min:0'],
            'state_name' => ['nullable', 'string', 'max:255'],
            'current_address1' => ['nullable', 'string', 'max:255'],
            'current_address2' => ['nullable', 'string', 'max:255'],
            'current_country' => ['nullable', 'string', 'max:255'],
            'current_state' => ['nullable', 'string', 'max:255'],
            'current_city' => ['nullable', 'string', 'max:255'],
            'current_zip' => ['nullable', 'string', 'max:255'],
            'same_as_current_address' => ['nullable', 'boolean'],
            'permanent_address1' => ['nullable', 'string', 'max:255'],
            'permanent_address2' => ['nullable', 'string', 'max:255'],
            'permanent_country' => ['nullable', 'string', 'max:255'],
            'permanent_state' => ['nullable', 'string', 'max:255'],
            'permanent_city' => ['nullable', 'string', 'max:255'],
            'permanent_zip' => ['nullable', 'string', 'max:255'],
            'payment_type' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_holder_name' => ['nullable', 'string', 'max:255'],
            'account_no' => ['nullable', 'string', 'max:255'],
            'ifsc_code' => ['nullable', 'string', 'max:255'],
            'emergency_name' => ['nullable', 'string', 'max:255'],
            'emergency_relationship' => ['nullable', 'string', 'max:255'],
            'emergency_address' => ['nullable', 'string', 'max:255'],
            'emergency_email' => ['nullable', 'email', 'max:255'],
            'emergency_mobile' => ['nullable', 'string', 'max:255'],
            'degree_name' => ['nullable', 'string', 'max:255'],
            'institution_name' => ['nullable', 'string', 'max:255'],
            'passing_year' => ['nullable', 'string', 'max:255'],
            'percentage' => ['nullable', 'string', 'max:255'],
            'previous_company_name' => ['nullable', 'string', 'max:255'],
            'previous_job_title' => ['nullable', 'string', 'max:255'],
            'previous_from_date' => ['nullable', 'date'],
            'previous_to_date' => ['nullable', 'date'],
            'probation_period' => ['nullable', 'string', 'max:255'],
            'probation_confirm_date' => ['nullable', 'date'],
            'separation_date' => ['nullable', 'date'],
            'last_working_day' => ['nullable', 'date'],
            'previous_year_experience' => ['nullable', 'string', 'max:255'],
            'years_completed' => ['nullable', 'string', 'max:255'],
            'overall_year_experience' => ['nullable', 'string', 'max:255'],
        ];

        $rules = array_merge($rules, $profileRules);

        $validated = $request->validate($rules);

        // Address copy logic for same_as_current_address
        if ($request->boolean('same_as_current_address')) {
            $validated['permanent_address1'] = $validated['current_address1'] ?? null;
            $validated['permanent_address2'] = $validated['current_address2'] ?? null;
            $validated['permanent_country'] = $validated['current_country'] ?? null;
            $validated['permanent_state'] = $validated['current_state'] ?? null;
            $validated['permanent_city'] = $validated['current_city'] ?? null;
            $validated['permanent_zip'] = $validated['current_zip'] ?? null;
            $validated['same_as_current_address'] = true;
        } else {
            $validated['same_as_current_address'] = false;
        }

        // Fill joining_date in profileData if set on User
        if (isset($validated['joining_date'])) {
            $validated['joining_date'] = $validated['joining_date'];
        }

        $targetRole = $currentUser->role === 'admin' ? $validated['role'] : 'employee';

        // Check if admin is trying to change their own role (prevent lockouts)
        if ($currentUser->id === $user->id && $targetRole !== 'admin') {
            return back()->withErrors(['role' => 'You cannot change your own Admin role.'])->withInput();
        }

        // Strict role hierarchy validation (prevents circular reporting)
        if ($targetRole === 'admin') {
            $validated['admin_id'] = null;
            $validated['manager_id'] = null;
        } elseif ($targetRole === 'manager') {
            $validated['manager_id'] = null;
            if (!empty($validated['admin_id'])) {
                $assignedAdmin = User::find($validated['admin_id']);
                if (!$assignedAdmin || $assignedAdmin->role !== 'admin') {
                    return back()->withErrors(['admin_id' => 'Managers can only be assigned to Admin users.'])->withInput();
                }
            }
        } else { // employee
            if ($currentUser->role === 'manager') {
                $validated['manager_id'] = $currentUser->id;
            }

            if (!empty($validated['manager_id'])) {
                $assignedManager = User::find($validated['manager_id']);
                if (!$assignedManager || $assignedManager->role !== 'manager') {
                    return back()->withErrors(['manager_id' => 'Employees can only be assigned to Manager users.'])->withInput();
                }
                if ($assignedManager->id === $user->id) {
                    return back()->withErrors(['manager_id' => 'A user cannot report to themselves.'])->withInput();
                }
            }

            if (!empty($validated['admin_id'])) {
                $assignedAdmin = User::find($validated['admin_id']);
                if (!$assignedAdmin || $assignedAdmin->role !== 'admin') {
                    return back()->withErrors(['admin_id' => 'Employees can only be assigned to Admin users.'])->withInput();
                }
                if ($assignedAdmin->id === $user->id) {
                    return back()->withErrors(['admin_id' => 'A user cannot report to themselves.'])->withInput();
                }
            }
        }

        if ($currentUser->role === 'manager') {
            $validated['role'] = 'employee';
        }

        $this->employeeService->update($user, $validated);

        return redirect()->route('employees.index')->with('success', 'Member updated successfully.');
    }

    public function destroy(User $user)
    {
        $currentUser = auth()->user();
        if ($currentUser->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        // Admin cannot delete themselves
        if ($currentUser->id === $user->id) {
            abort(403, 'You cannot delete your own account.');
        }

        $this->employeeService->delete($user);

        return redirect()->route('employees.index')->with('success', 'Member deleted.');
    }

    /**
     * Reset the employee's password to the default password.
     */
    public function resetPassword(User $user): RedirectResponse
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $defaultPassword = config('employees.default_employee_password');
        if (empty($defaultPassword)) {
            return back()->withErrors(['password' => 'The DEFAULT_EMPLOYEE_PASSWORD environment variable is not configured.']);
        }

        $user->password = \Illuminate\Support\Facades\Hash::make($defaultPassword);
        $user->must_change_password = true;
        $user->save();

        return back()->with('success', "Password for {$user->name} has been reset to default successfully. They will be forced to change it on next login.");
    }
}