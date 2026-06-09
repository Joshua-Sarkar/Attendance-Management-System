<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use App\Services\EmployeeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

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
        $employees = User::with(['department', 'manager'])
                         ->latest()
                         ->get();

        return view('employees.index', compact('employees'));
    }

    // =========================================================
    // Create — show blank form
    // =========================================================

    public function create(): View
    {
        $departments = Department::orderBy('name')->get();

        // Only manager-role users appear in the manager dropdown
        $managers = User::where('role', 'manager')
                        ->orderBy('name')
                        ->get();

        $suggestedEmployeeId = $this->generateEmployeeId();

        return view('employees.create', compact(
            'departments',
            'managers',
            'suggestedEmployeeId'
        ));
    }

    // =========================================================
    // Store — validate and save
    // =========================================================

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id'   => ['required', 'string', 'max:20',  'unique:users,employee_id'],
            'name'          => ['required', 'string', 'max:100'],
            'email'         => ['required', 'email',  'max:150', 'unique:users,email'],
            'password'      => ['required', 'confirmed', 'min:8'],
            'role'          => ['required', 'string', 'in:admin,manager,employee'],
            'status'        => ['required', 'string', 'in:active,inactive,resigned'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'manager_id'    => ['nullable', 'exists:users,id'],
        ]);

        $employee = $this->employeeService->create($validated);

        return redirect()
            ->route('employees.index')
            ->with('success', "Employee {$employee->name} ({$employee->employee_id}) created successfully.");
    }

    // =========================================================
    // Private — Employee ID generator
    // Kept here instead of the model since you don't want
    // generateEmployeeId() on the User model.
    // When CSV import is added, move this into EmployeeService.
    // =========================================================

    private function generateEmployeeId(): string
    {
        $latest = User::orderBy('id', 'desc')->value('employee_id');

        if ($latest && str_starts_with($latest, 'EMP-')) {
            $number = (int) substr($latest, 4) + 1;
        } else {
            $number = 1;
        }

        return 'EMP-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
    public function edit(User $user)
{
    $departments = Department::all();
    $managers = User::where('role', 'manager')
                    ->where('status', 'active')
                    ->where('id', '!=', $user->id)
                    ->get();

    return view('employees.edit', compact('user', 'departments', 'managers'));
}

public function update(Request $request, User $user)
{
    $validated = $request->validate([
        'employee_id' => ['required', 'string', 'max:50', Rule::unique('users')->ignore($user->id)],
        'name'        => ['required', 'string', 'max:255'],
        'email'       => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        'password'    => ['nullable', 'string', 'min:8', 'confirmed'],
        'department_id' => ['required', 'exists:departments,id'],
        'role'        => ['required', 'in:admin,manager,employee'],
        'status'      => ['required', 'in:active,inactive,resigned'],
        'manager_id'  => ['nullable', 'exists:users,id'],
    ]);

    $this->employeeService->update($user, $validated);

    return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
}

public function destroy(User $user)
{
    $this->employeeService->delete($user);

    return redirect()->route('employees.index')->with('success', 'Employee deleted.');
}
}