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



                        </div>

                        <!-- Additional Profile Information Sections -->
                        <div class="mt-8 space-y-4">
                            <h3 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-4">Additional Profile Information</h3>
                            
                            <!-- 1. Personal Section -->
                            <details class="group bg-gray-50 rounded-lg p-4 border border-gray-200" open>
                                <summary class="font-semibold text-gray-700 cursor-pointer focus:outline-none flex items-center justify-between select-none">
                                    <span>Section "Personal"</span>
                                    <span class="transition group-open:rotate-180">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </span>
                                </summary>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label for="father_name" class="block text-sm font-medium text-gray-700">Father's Name</label>
                                        <input type="text" name="father_name" id="father_name" value="{{ old('father_name') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('father_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="mother_name" class="block text-sm font-medium text-gray-700">Mother's Name</label>
                                        <input type="text" name="mother_name" id="mother_name" value="{{ old('mother_name') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('mother_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="gender" class="block text-sm font-medium text-gray-700">Gender</label>
                                        <select name="gender" id="gender" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                            <option value="">Select Gender</option>
                                            <option value="Male" {{ old('gender') === 'Male' ? 'selected' : '' }}>Male</option>
                                            <option value="Female" {{ old('gender') === 'Female' ? 'selected' : '' }}>Female</option>
                                            <option value="Other" {{ old('gender') === 'Other' ? 'selected' : '' }}>Other</option>
                                        </select>
                                        @error('gender') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="date_of_birth" class="block text-sm font-medium text-gray-700">Date of Birth</label>
                                        <input type="date" name="date_of_birth" id="date_of_birth" value="{{ old('date_of_birth') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('date_of_birth') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="marital_status" class="block text-sm font-medium text-gray-700">Marital Status</label>
                                        <input type="text" name="marital_status" id="marital_status" value="{{ old('marital_status') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('marital_status') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="date_of_marriage" class="block text-sm font-medium text-gray-700">Date of Marriage</label>
                                        <input type="date" name="date_of_marriage" id="date_of_marriage" value="{{ old('date_of_marriage') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('date_of_marriage') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="nationality" class="block text-sm font-medium text-gray-700">Nationality</label>
                                        <input type="text" name="nationality" id="nationality" value="{{ old('nationality') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('nationality') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="blood_group" class="block text-sm font-medium text-gray-700">Blood Group</label>
                                        <input type="text" name="blood_group" id="blood_group" value="{{ old('blood_group') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('blood_group') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="personal_email" class="block text-sm font-medium text-gray-700">Personal Email</label>
                                        <input type="email" name="personal_email" id="personal_email" value="{{ old('personal_email') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('personal_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="mobile_no" class="block text-sm font-medium text-gray-700">Mobile No</label>
                                        <input type="text" name="mobile_no" id="mobile_no" value="{{ old('mobile_no') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('mobile_no') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </details>

                            <!-- 2. Government IDs Section -->
                            <details class="group bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <summary class="font-semibold text-gray-700 cursor-pointer focus:outline-none flex items-center justify-between select-none">
                                    <span>Section "Government IDs"</span>
                                    <span class="transition group-open:rotate-180">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </span>
                                </summary>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label for="pf_uan" class="block text-sm font-medium text-gray-700">PF UAN</label>
                                        <input type="text" name="pf_uan" id="pf_uan" value="{{ old('pf_uan') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('pf_uan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="passport_no" class="block text-sm font-medium text-gray-700">Passport No</label>
                                        <input type="text" name="passport_no" id="passport_no" value="{{ old('passport_no') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('passport_no') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="aadhar_card" class="block text-sm font-medium text-gray-700">Aadhar Card</label>
                                        <input type="text" name="aadhar_card" id="aadhar_card" value="{{ old('aadhar_card') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('aadhar_card') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="pan" class="block text-sm font-medium text-gray-700">PAN</label>
                                        <input type="text" name="pan" id="pan" value="{{ old('pan') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('pan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="pf_no" class="block text-sm font-medium text-gray-700">PF No</label>
                                        <input type="text" name="pf_no" id="pf_no" value="{{ old('pf_no') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('pf_no') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="esi_number" class="block text-sm font-medium text-gray-700">ESI Number</label>
                                        <input type="text" name="esi_number" id="esi_number" value="{{ old('esi_number') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('esi_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="date_of_gratuity" class="block text-sm font-medium text-gray-700">Date of Gratuity</label>
                                        <input type="date" name="date_of_gratuity" id="date_of_gratuity" value="{{ old('date_of_gratuity') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('date_of_gratuity') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </details>

                            <!-- 3. Employment Section -->
                            <details class="group bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <summary class="font-semibold text-gray-700 cursor-pointer focus:outline-none flex items-center justify-between select-none">
                                    <span>Section "Employment"</span>
                                    <span class="transition group-open:rotate-180">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </span>
                                </summary>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label for="payroll_type" class="block text-sm font-medium text-gray-700">Payroll Type</label>
                                        <input type="text" name="payroll_type" id="payroll_type" value="{{ old('payroll_type') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('payroll_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="contract_end_date" class="block text-sm font-medium text-gray-700">Contract End Date</label>
                                        <input type="date" name="contract_end_date" id="contract_end_date" value="{{ old('contract_end_date') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('contract_end_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="office_landline" class="block text-sm font-medium text-gray-700">Office Landline</label>
                                        <input type="text" name="office_landline" id="office_landline" value="{{ old('office_landline') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('office_landline') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="leave_rule" class="block text-sm font-medium text-gray-700">Leave Rule</label>
                                        <input type="text" name="leave_rule" id="leave_rule" value="{{ old('leave_rule') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('leave_rule') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="shift" class="block text-sm font-medium text-gray-700">Shift</label>
                                        <input type="text" name="shift" id="shift" value="{{ old('shift') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('shift') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="designation" class="block text-sm font-medium text-gray-700">Designation</label>
                                        <input type="text" name="designation" id="designation" value="{{ old('designation') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('designation') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="grade" class="block text-sm font-medium text-gray-700">Grade</label>
                                        <input type="text" name="grade" id="grade" value="{{ old('grade') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('grade') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="employee_type" class="block text-sm font-medium text-gray-700">Employee Type</label>
                                        <input type="text" name="employee_type" id="employee_type" value="{{ old('employee_type') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('employee_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="company" class="block text-sm font-medium text-gray-700">Company</label>
                                        <input type="text" name="company" id="company" value="{{ old('company') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('company') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="location" class="block text-sm font-medium text-gray-700">Location</label>
                                        <input type="text" name="location" id="location" value="{{ old('location') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('location') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="biometric_id" class="block text-sm font-medium text-gray-700">Biometric ID</label>
                                        <input type="text" name="biometric_id" id="biometric_id" value="{{ old('biometric_id') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('biometric_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="hiring_source" class="block text-sm font-medium text-gray-700">Hiring Source</label>
                                        <input type="text" name="hiring_source" id="hiring_source" value="{{ old('hiring_source') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('hiring_source') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="source_of_verification" class="block text-sm font-medium text-gray-700">Source of Verification</label>
                                        <input type="text" name="source_of_verification" id="source_of_verification" value="{{ old('source_of_verification') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('source_of_verification') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="city_type" class="block text-sm font-medium text-gray-700">City Type</label>
                                        <input type="text" name="city_type" id="city_type" value="{{ old('city_type') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('city_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="notice_days" class="block text-sm font-medium text-gray-700">Notice Days</label>
                                        <input type="number" name="notice_days" id="notice_days" value="{{ old('notice_days') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('notice_days') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="state_name" class="block text-sm font-medium text-gray-700">State Name</label>
                                        <input type="text" name="state_name" id="state_name" value="{{ old('state_name') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('state_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </details>

                            <!-- 4. Current Address Section -->
                            <details class="group bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <summary class="font-semibold text-gray-700 cursor-pointer focus:outline-none flex items-center justify-between select-none">
                                    <span>Section "Current Address"</span>
                                    <span class="transition group-open:rotate-180">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </span>
                                </summary>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label for="current_address1" class="block text-sm font-medium text-gray-700">Current Address Line 1</label>
                                        <input type="text" name="current_address1" id="current_address1" value="{{ old('current_address1') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('current_address1') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="current_address2" class="block text-sm font-medium text-gray-700">Current Address Line 2</label>
                                        <input type="text" name="current_address2" id="current_address2" value="{{ old('current_address2') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('current_address2') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="current_country" class="block text-sm font-medium text-gray-700">Current Country</label>
                                        <input type="text" name="current_country" id="current_country" value="{{ old('current_country') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('current_country') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="current_state" class="block text-sm font-medium text-gray-700">Current State</label>
                                        <input type="text" name="current_state" id="current_state" value="{{ old('current_state') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('current_state') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="current_city" class="block text-sm font-medium text-gray-700">Current City</label>
                                        <input type="text" name="current_city" id="current_city" value="{{ old('current_city') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('current_city') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="current_zip" class="block text-sm font-medium text-gray-700">Current Zip Code</label>
                                        <input type="text" name="current_zip" id="current_zip" value="{{ old('current_zip') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('current_zip') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </details>

                            <!-- 5. Permanent Address Section -->
                            <details class="group bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <summary class="font-semibold text-gray-700 cursor-pointer focus:outline-none flex items-center justify-between select-none">
                                    <span>Section "Permanent Address"</span>
                                    <span class="transition group-open:rotate-180">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </span>
                                </summary>
                                <div class="mt-4">
                                    <label class="inline-flex items-center mb-4">
                                        <input type="checkbox" name="same_as_current_address" id="same_as_current_address" value="1" {{ old('same_as_current_address') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        <span class="ml-2 text-sm text-gray-600">Same as Current Address</span>
                                    </label>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="permanent_address1" class="block text-sm font-medium text-gray-700">Permanent Address Line 1</label>
                                            <input type="text" name="permanent_address1" id="permanent_address1" value="{{ old('permanent_address1') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                            @error('permanent_address1') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label for="permanent_address2" class="block text-sm font-medium text-gray-700">Permanent Address Line 2</label>
                                            <input type="text" name="permanent_address2" id="permanent_address2" value="{{ old('permanent_address2') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                            @error('permanent_address2') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label for="permanent_country" class="block text-sm font-medium text-gray-700">Permanent Country</label>
                                            <input type="text" name="permanent_country" id="permanent_country" value="{{ old('permanent_country') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                            @error('permanent_country') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label for="permanent_state" class="block text-sm font-medium text-gray-700">Permanent State</label>
                                            <input type="text" name="permanent_state" id="permanent_state" value="{{ old('permanent_state') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                            @error('permanent_state') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label for="permanent_city" class="block text-sm font-medium text-gray-700">Permanent City</label>
                                            <input type="text" name="permanent_city" id="permanent_city" value="{{ old('permanent_city') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                            @error('permanent_city') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label for="permanent_zip" class="block text-sm font-medium text-gray-700">Permanent Zip Code</label>
                                            <input type="text" name="permanent_zip" id="permanent_zip" value="{{ old('permanent_zip') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                            @error('permanent_zip') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                </div>
                            </details>

                            <!-- 6. Bank Details Section -->
                            <details class="group bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <summary class="font-semibold text-gray-700 cursor-pointer focus:outline-none flex items-center justify-between select-none">
                                    <span>Section "Bank Details"</span>
                                    <span class="transition group-open:rotate-180">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </span>
                                </summary>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label for="payment_type" class="block text-sm font-medium text-gray-700">Payment Type</label>
                                        <input type="text" name="payment_type" id="payment_type" value="{{ old('payment_type') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('payment_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="bank_name" class="block text-sm font-medium text-gray-700">Bank Name</label>
                                        <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('bank_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="account_holder_name" class="block text-sm font-medium text-gray-700">Account Holder Name</label>
                                        <input type="text" name="account_holder_name" id="account_holder_name" value="{{ old('account_holder_name') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('account_holder_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="account_no" class="block text-sm font-medium text-gray-700">Account No</label>
                                        <input type="text" name="account_no" id="account_no" value="{{ old('account_no') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('account_no') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="ifsc_code" class="block text-sm font-medium text-gray-700">IFSC Code</label>
                                        <input type="text" name="ifsc_code" id="ifsc_code" value="{{ old('ifsc_code') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('ifsc_code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </details>

                            <!-- 7. Emergency Contact Section -->
                            <details class="group bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <summary class="font-semibold text-gray-700 cursor-pointer focus:outline-none flex items-center justify-between select-none">
                                    <span>Section "Emergency Contact"</span>
                                    <span class="transition group-open:rotate-180">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </span>
                                </summary>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label for="emergency_name" class="block text-sm font-medium text-gray-700">Emergency Name</label>
                                        <input type="text" name="emergency_name" id="emergency_name" value="{{ old('emergency_name') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('emergency_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="emergency_relationship" class="block text-sm font-medium text-gray-700">Emergency Relationship</label>
                                        <input type="text" name="emergency_relationship" id="emergency_relationship" value="{{ old('emergency_relationship') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('emergency_relationship') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="emergency_address" class="block text-sm font-medium text-gray-700">Emergency Address</label>
                                        <input type="text" name="emergency_address" id="emergency_address" value="{{ old('emergency_address') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('emergency_address') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="emergency_email" class="block text-sm font-medium text-gray-700">Emergency Email</label>
                                        <input type="email" name="emergency_email" id="emergency_email" value="{{ old('emergency_email') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('emergency_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="emergency_mobile" class="block text-sm font-medium text-gray-700">Emergency Mobile</label>
                                        <input type="text" name="emergency_mobile" id="emergency_mobile" value="{{ old('emergency_mobile') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('emergency_mobile') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </details>

                            <!-- 8. Education Section -->
                            <details class="group bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <summary class="font-semibold text-gray-700 cursor-pointer focus:outline-none flex items-center justify-between select-none">
                                    <span>Section "Education"</span>
                                    <span class="transition group-open:rotate-180">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </span>
                                </summary>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label for="degree_name" class="block text-sm font-medium text-gray-700">Degree Name</label>
                                        <input type="text" name="degree_name" id="degree_name" value="{{ old('degree_name') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('degree_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="institution_name" class="block text-sm font-medium text-gray-700">Institution Name</label>
                                        <input type="text" name="institution_name" id="institution_name" value="{{ old('institution_name') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('institution_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="passing_year" class="block text-sm font-medium text-gray-700">Passing Year</label>
                                        <input type="text" name="passing_year" id="passing_year" value="{{ old('passing_year') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('passing_year') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="percentage" class="block text-sm font-medium text-gray-700">Percentage</label>
                                        <input type="text" name="percentage" id="percentage" value="{{ old('percentage') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('percentage') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </details>

                            <!-- 9. Previous Employment Section -->
                            <details class="group bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <summary class="font-semibold text-gray-700 cursor-pointer focus:outline-none flex items-center justify-between select-none">
                                    <span>Section "Previous Employment"</span>
                                    <span class="transition group-open:rotate-180">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </span>
                                </summary>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label for="previous_company_name" class="block text-sm font-medium text-gray-700">Previous Company Name</label>
                                        <input type="text" name="previous_company_name" id="previous_company_name" value="{{ old('previous_company_name') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('previous_company_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="previous_job_title" class="block text-sm font-medium text-gray-700">Previous Job Title</label>
                                        <input type="text" name="previous_job_title" id="previous_job_title" value="{{ old('previous_job_title') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('previous_job_title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="previous_from_date" class="block text-sm font-medium text-gray-700">From Date</label>
                                        <input type="date" name="previous_from_date" id="previous_from_date" value="{{ old('previous_from_date') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('previous_from_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="previous_to_date" class="block text-sm font-medium text-gray-700">To Date</label>
                                        <input type="date" name="previous_to_date" id="previous_to_date" value="{{ old('previous_to_date') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('previous_to_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </details>

                            <!-- 10. Tenure Section -->
                            <details class="group bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <summary class="font-semibold text-gray-700 cursor-pointer focus:outline-none flex items-center justify-between select-none">
                                    <span>Section "Tenure"</span>
                                    <span class="transition group-open:rotate-180">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </span>
                                </summary>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label for="probation_period" class="block text-sm font-medium text-gray-700">Probation Period</label>
                                        <input type="text" name="probation_period" id="probation_period" value="{{ old('probation_period') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('probation_period') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="probation_confirm_date" class="block text-sm font-medium text-gray-700">Probation Confirm Date</label>
                                        <input type="date" name="probation_confirm_date" id="probation_confirm_date" value="{{ old('probation_confirm_date') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('probation_confirm_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="separation_date" class="block text-sm font-medium text-gray-700">Separation Date</label>
                                        <input type="date" name="separation_date" id="separation_date" value="{{ old('separation_date') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('separation_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="last_working_day" class="block text-sm font-medium text-gray-700">Last Working Day (LWD)</label>
                                        <input type="date" name="last_working_day" id="last_working_day" value="{{ old('last_working_day') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('last_working_day') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="previous_year_experience" class="block text-sm font-medium text-gray-700">Previous Experience (Years)</label>
                                        <input type="text" name="previous_year_experience" id="previous_year_experience" value="{{ old('previous_year_experience') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('previous_year_experience') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="years_completed" class="block text-sm font-medium text-gray-700">Years Completed</label>
                                        <input type="text" name="years_completed" id="years_completed" value="{{ old('years_completed') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('years_completed') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="overall_year_experience" class="block text-sm font-medium text-gray-700">Overall Experience (Years)</label>
                                        <input type="text" name="overall_year_experience" id="overall_year_experience" value="{{ old('overall_year_experience') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                        @error('overall_year_experience') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </details>
                        </div>

                        <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const checkbox = document.getElementById('same_as_current_address');
                            const currentFields = [
                                'current_address1', 'current_address2', 'current_country', 
                                'current_state', 'current_city', 'current_zip'
                            ];
                            const permanentFields = [
                                'permanent_address1', 'permanent_address2', 'permanent_country', 
                                'permanent_state', 'permanent_city', 'permanent_zip'
                            ];

                            function copyAddress() {
                                if (checkbox.checked) {
                                    currentFields.forEach((id, index) => {
                                        const currentVal = document.getElementById(id).value;
                                        const permField = document.getElementById(permanentFields[index]);
                                        permField.value = currentVal;
                                        permField.disabled = true;
                                        permField.classList.add('bg-gray-100', 'cursor-not-allowed');
                                    });
                                } else {
                                    permanentFields.forEach(id => {
                                        const permField = document.getElementById(id);
                                        permField.disabled = false;
                                        permField.classList.remove('bg-gray-100', 'cursor-not-allowed');
                                    });
                                }
                            }

                            checkbox.addEventListener('change', copyAddress);
                            
                            currentFields.forEach((id, index) => {
                                document.getElementById(id).addEventListener('input', function() {
                                    if (checkbox.checked) {
                                        document.getElementById(permanentFields[index]).value = this.value;
                                    }
                                });
                            });

                            copyAddress();
                        });
                        </script>

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