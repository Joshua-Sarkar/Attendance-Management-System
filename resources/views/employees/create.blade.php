<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add Workforce Member') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">

                    <form method="POST" action="{{ route('employees.store') }}">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">
                                    Name
                                </label>

                                <input
                                    type="text"
                                    name="name"
                                    id="name"
                                    value="{{ old('name') }}"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >

                                @error('name')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">
                                    Email
                                </label>

                                <input
                                    type="email"
                                    name="email"
                                    id="email"
                                    value="{{ old('email') }}"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >

                                @error('email')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Phone -->
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700">
                                    Phone Number
                                </label>

                                <input
                                    type="text"
                                    name="phone"
                                    id="phone"
                                    value="{{ old('phone') }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >

                                @error('phone')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Joining Date -->
                            <div>
                                <label for="joining_date" class="block text-sm font-medium text-gray-700">
                                    Joining Date
                                </label>

                                <input
                                    type="date"
                                    name="joining_date"
                                    id="joining_date"
                                    value="{{ old('joining_date', today()->format('Y-m-d')) }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >

                                @error('joining_date')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Status -->
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700">
                                    Status
                                </label>

                                <select
                                    name="status"
                                    id="status"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Select Status</option>
                                    <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>
                                        Active
                                    </option>
                                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>
                                        Inactive
                                    </option>
                                    <option value="resigned" {{ old('status') == 'resigned' ? 'selected' : '' }}>
                                        Resigned
                                    </option>
                                </select>

                                @error('status')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Department -->
                            <div>
                                <label for="department_id" class="block text-sm font-medium text-gray-700">
                                    Department
                                </label>

                                <select
                                    name="department_id"
                                    id="department_id"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Select Department</option>
                                    @foreach($departments as $department)
                                        <option
                                            value="{{ $department->id }}"
                                            {{ old('department_id') == $department->id ? 'selected' : '' }}
                                        >
                                            {{ $department->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('department_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Role -->
                            @if(auth()->user()->role === 'admin')
                                <div>
                                    <label for="role" class="block text-sm font-medium text-gray-700">
                                        Role
                                    </label>

                                    <select
                                        name="role"
                                        id="role"
                                        required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="">Select Role</option>
                                        <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>
                                            Admin
                                        </option>
                                        <option value="manager" {{ old('role') == 'manager' ? 'selected' : '' }}>
                                            Manager
                                        </option>
                                        <option value="employee" {{ old('role', 'employee') == 'employee' ? 'selected' : '' }}>
                                            Employee
                                        </option>
                                    </select>

                                    @error('role')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            @else
                                <input type="hidden" name="role" value="employee">
                            @endif

                            <!-- Assigned Admin -->
                            <div>
                                <label for="admin_id" class="block text-sm font-medium text-gray-700">
                                    Assigned Admin (optional)
                                </label>

                                <select
                                    name="admin_id"
                                    id="admin_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">No Admin</option>
                                    @foreach($admins as $admin)
                                        <option
                                            value="{{ $admin->id }}"
                                            {{ old('admin_id') == $admin->id ? 'selected' : '' }}
                                        >
                                            {{ $admin->name }} ({{ $admin->employee_id }})
                                        </option>
                                    @endforeach
                                </select>

                                @error('admin_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Assigned Manager -->
                            @if(auth()->user()->role === 'admin')
                                <div>
                                    <label for="manager_id" class="block text-sm font-medium text-gray-700">
                                        Assigned Manager (optional)
                                    </label>

                                    <select
                                        name="manager_id"
                                        id="manager_id"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="">No Manager</option>
                                        @foreach($managers as $manager)
                                            <option
                                                value="{{ $manager->id }}"
                                                {{ old('manager_id') == $manager->id ? 'selected' : '' }}
                                            >
                                                {{ $manager->name }} ({{ $manager->employee_id }})
                                            </option>
                                        @endforeach
                                    </select>

                                    @error('manager_id')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            @else
                                <input type="hidden" name="manager_id" value="{{ auth()->user()->id }}">
                            @endif

                            <!-- Password -->
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700">
                                    Password (Optional)
                                </label>

                                <input
                                    type="password"
                                    name="password"
                                    id="password"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Leave blank to auto-generate"
                                >
                                <p class="mt-1 text-xs text-gray-500">If left blank, a secure temporary password will be auto-generated.</p>

                                @error('password')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Password Confirmation -->
                            <div>
                                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                                    Confirm Password (Optional)
                                </label>

                                <input
                                    type="password"
                                    name="password_confirmation"
                                    id="password_confirmation"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Confirm new password"
                                >
                            </div>

                        </div>

                        <div class="mt-6 flex items-center gap-3">
                            <button
                                type="submit"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition"
                            >
                                Add Member
                            </button>

                            <a
                                href="{{ route('employees.index') }}"
                                class="inline-flex items-center px-4 py-2 bg-gray-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-600"
                            >
                                Cancel
                            </a>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>