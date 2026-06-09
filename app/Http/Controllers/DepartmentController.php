<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::latest()->get();

        return view('departments.index', compact('departments'));
    }

    public function create()
    {
        return view('departments.create');
    }

    public function store(Request $request)
    {
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
        return view('departments.edit', compact('department'));
    }

    public function update(Request $request, Department $department)
    {
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
        $department->delete();

        return redirect()
            ->route('departments.index')
            ->with('success', 'Department deleted successfully.');
    }
}