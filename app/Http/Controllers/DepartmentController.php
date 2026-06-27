<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if ($user->role === 'employee') {
            abort(403, 'Unauthorized action.');
        }

        $departments = Department::with(['users.manager'])->latest()->get();
        
        return view('departments.index', compact('departments'));
    }

    public function create()
    {
        $user = auth()->user();
        if ($user->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        return view('departments.create');
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if ($user->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|max:100',
            'code' => 'required|max:10|unique:departments,code',
            'description' => 'nullable|max:500',
        ]);

        Department::create([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'description' => $request->description,
        ]);

        return redirect()
            ->route('departments.index')
            ->with('success', 'Department created successfully.');
    }

    public function edit(Department $department)
    {
        $user = auth()->user();
        if ($user->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        return view('departments.edit', compact('department'));
    }

    public function update(Request $request, Department $department)
    {
        $user = auth()->user();
        if ($user->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|max:100',
            'code' => 'required|max:10|unique:departments,code,' . $department->id,
            'description' => 'nullable|max:500',
        ]);

        $department->update([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'description' => $request->description,
        ]);

        return redirect()
            ->route('departments.index')
            ->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department)
    {
        $user = auth()->user();
        if ($user->role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $department->delete();

        return redirect()
            ->route('departments.index')
            ->with('success', 'Department deleted successfully.');
    }

    public function show(Department $department)
    {
        $user = auth()->user();
        if ($user->role === 'employee') {
            abort(403, 'Unauthorized action.');
        }

        $query = $department->users()->with(['department', 'manager', 'admin', 'employeeProfile']);

        if ($user->role === 'manager') {
            $query->where('role', 'employee')->where('manager_id', $user->id);
        }

        $employees = $query->latest()->get();

        return view('departments.show', compact('department', 'employees'));
    }
}