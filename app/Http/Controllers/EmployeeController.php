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
    ) {}

    // =========================================================
    // Index — list all employees
    // =========================================================

    public function index(): View
    {
        $currentUser = auth()->user();
        if ($currentUser->role === 'employee') {
            abort(403, 'Unauthorized action.');
        }

        if ($currentUser->role === 'admin') {
            $employees = User::with(['department', 'manager', 'admin'])
                             ->latest()
                             ->get();
        } else { // manager
            $employees = User::where('role', 'employee')
                             ->where('manager_id', $currentUser->id)
                             ->with(['department', 'manager', 'admin'])
                             ->latest()
                             ->get();
        }

        return view('employees.index', compact('employees'));
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
            'name'          => ['required', 'string', 'max:100'],
            'email'         => ['required', 'email',  'max:150', 'unique:users,email'],
            'password'      => ['nullable', 'confirmed', 'min:8'],
            'status'        => ['required', 'string', 'in:active,inactive,resigned'],
            'phone'         => ['nullable', 'string', 'max:20'],
            'joining_date'  => ['nullable', 'date'],
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

        $validated = $request->validate($rules);

        // Auto-generate employee ID
        $validated['employee_id'] = $this->generateEmployeeId();

        // Optional password override
        $manuallySet = false;
        if (!empty($validated['password'])) {
            $tempPassword = $validated['password'];
            $manuallySet = true;
        } else {
            $tempPassword = Str::random(10);
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

        if ($manuallySet) {
            return redirect()
                ->route('employees.index')
                ->with('success', "Member {$employee->name} ({$employee->employee_id}) created successfully.");
        }

        return redirect()
            ->route('employees.index')
            ->with('success_provisioned', [
                'name' => $employee->name,
                'employee_id' => $employee->employee_id,
                'password' => $tempPassword,
            ]);
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
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password'    => ['nullable', 'string', 'min:8', 'confirmed'],
            'phone'       => ['nullable', 'string', 'max:20'],
            'joining_date' => ['nullable', 'date'],
            'department_id' => ['required', 'exists:departments,id'],
            'status'      => ['required', 'in:active,inactive,resigned'],
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

        $validated = $request->validate($rules);

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
        if ($currentUser->role === 'employee') {
            abort(403, 'Unauthorized action.');
        }

        // Manager can only delete employees assigned to them
        if ($currentUser->role === 'manager') {
            if ($user->role !== 'employee' || $user->manager_id !== $currentUser->id) {
                abort(403, 'Unauthorized action.');
            }
        }

        // Admin cannot delete themselves
        if ($currentUser->id === $user->id) {
            abort(403, 'You cannot delete your own account.');
        }

        $this->employeeService->delete($user);

        return redirect()->route('employees.index')->with('success', 'Member deleted.');
    }
}