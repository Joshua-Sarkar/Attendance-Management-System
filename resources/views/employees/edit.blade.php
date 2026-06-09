<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Employee — {{ $user->name }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <form method="POST" action="{{ route('employees.update', $user) }}">
                    @csrf
                    @method('PUT')

                    {{-- Employee ID --}}
                    <div class="mb-4">
                        <x-input-label for="employee_id" value="Employee ID" />
                        <x-text-input
                            id="employee_id"
                            name="employee_id"
                            type="text"
                            class="mt-1 block w-full"
                            value="{{ old('employee_id', $user->employee_id) }}"
                            required
                        />
                        <x-input-error :messages="$errors->get('employee_id')" class="mt-2" />
                    </div>

                    {{-- Name --}}
                    <div class="mb-4">
                        <x-input-label for="name" value="Full Name" />
                        <x-text-input
                            id="name"
                            name="name"
                            type="text"
                            class="mt-1 block w-full"
                            value="{{ old('name', $user->name) }}"
                            required
                        />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    {{-- Email --}}
                    <div class="mb-4">
                        <x-input-label for="email" value="Email Address" />
                        <x-text-input
                            id="email"
                            name="email"
                            type="email"
                            class="mt-1 block w-full"
                            value="{{ old('email', $user->email) }}"
                            required
                        />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    {{-- Password (optional on edit) --}}
                    <div class="mb-4">
                        <x-input-label for="password" value="New Password" />
                        <x-text-input
                            id="password"
                            name="password"
                            type="password"
                            class="mt-1 block w-full"
                            placeholder="Leave blank to keep current password"
                        />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    {{-- Password Confirmation --}}
                    <div class="mb-4">
                        <x-input-label for="password_confirmation" value="Confirm New Password" />
                        <x-text-input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            class="mt-1 block w-full"
                            placeholder="Leave blank to keep current password"
                        />
                    </div>

                    {{-- Department --}}
                    <div class="mb-4">
                        <x-input-label for="department_id" value="Department" />
                        <select
                            id="department_id"
                            name="department_id"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            required
                        >
                            <option value="">— Select Department —</option>
                            @foreach ($departments as $department)
                                <option
                                    value="{{ $department->id }}"
                                    {{ old('department_id', $user->department_id) == $department->id ? 'selected' : '' }}
                                >
                                    {{ $department->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('department_id')" class="mt-2" />
                    </div>

                    {{-- Role --}}
                    <div class="mb-4">
                        <x-input-label for="role" value="Role" />
                        <select
                            id="role"
                            name="role"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            required
                        >
                            <option value="">— Select Role —</option>
                            @foreach (['admin', 'manager', 'employee'] as $role)
                                <option
                                    value="{{ $role }}"
                                    {{ old('role', $user->role) === $role ? 'selected' : '' }}
                                >
                                    {{ ucfirst($role) }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('role')" class="mt-2" />
                    </div>

                    {{-- Status --}}
                    <div class="mb-4">
                        <x-input-label for="status" value="Status" />
                        <select
                            id="status"
                            name="status"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            required
                        >
                            <option value="">— Select Status —</option>
                            @foreach (['active', 'inactive', 'resigned'] as $status)
                                <option
                                    value="{{ $status }}"
                                    {{ old('status', $user->status) === $status ? 'selected' : '' }}
                                >
                                    {{ ucfirst($status) }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>

                    {{-- Manager --}}
                    <div class="mb-6">
                        <x-input-label for="manager_id" value="Manager (optional)" />
                        <select
                            id="manager_id"
                            name="manager_id"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        >
                            <option value="">— No Manager —</option>
                            @foreach ($managers as $manager)
                                <option
                                    value="{{ $manager->id }}"
                                    {{ old('manager_id', $user->manager_id) == $manager->id ? 'selected' : '' }}
                                >
                                    {{ $manager->name }} ({{ $manager->employee_id }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('manager_id')" class="mt-2" />
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-4">
                        <x-primary-button>Save Changes</x-primary-button>
                        
                            href="{{ route('employees.index') }}"
                            class="text-sm text-gray-600 hover:text-gray-900 underline"
                        >
                            Cancel
                        </a>
                    </div>

                </form>
            </div>
        </div>
    </div>
</x-app-layout>