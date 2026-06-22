<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-vellum leading-tight font-display">
            Edit Workforce Member — {{ $user->name }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="glass-panel overflow-hidden shadow-sm sm:rounded-lg border border-hairline">

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

                    {{-- Phone --}}
                    <div class="mb-4">
                        <x-input-label for="phone" value="Phone Number" />
                        <x-text-input
                            id="phone"
                            name="phone"
                            type="text"
                            class="mt-1 block w-full"
                            value="{{ old('phone', $user->phone) }}"
                        />
                        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                    </div>

                    {{-- Joining Date --}}
                    <div class="mb-4">
                        <x-input-label for="joining_date" value="Joining Date" />
                        <x-text-input
                            id="joining_date"
                            name="joining_date"
                            type="date"
                            class="mt-1 block w-full"
                            value="{{ old('joining_date', $user->joining_date?->format('Y-m-d')) }}"
                        />
                        <x-input-error :messages="$errors->get('joining_date')" class="mt-2" />
                    </div>

                    {{-- Department --}}
                    <div class="mb-4">
                        <x-input-label for="department_id" value="Department" />
                        <select
                            id="department_id"
                            name="department_id"
                            class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1"
                            required
                        >
                            <option value="" class="bg-surface" class="bg-surface">— Select Department —</option>
                            @foreach ($departments as $department)
                                <option
                                    value="{{ $department->id }}"
                                    {{ old('department_id', $user->department_id) == $department->id ? 'selected' : '' }}
                                    class="bg-surface"
                                >
                                    {{ $department->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('department_id')" class="mt-2" />
                    </div>

                    {{-- Role --}}
                    @if(auth()->user()->role === 'admin')
                        <div class="mb-4">
                            <x-input-label for="role" value="Role" />
                            <select
                                id="role"
                                name="role"
                                class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1"
                                required
                            >
                                <option value="" class="bg-surface" class="bg-surface">— Select Role —</option>
                                @foreach (['admin', 'manager', 'employee'] as $role)
                                    <option
                                        value="{{ $role }}"
                                        {{ old('role', $user->role) === $role ? 'selected' : '' }}
                                        class="bg-surface"
                                    >
                                        {{ ucfirst($role) }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('role')" class="mt-2" />
                        </div>
                    @else
                        <input type="hidden" name="role" value="{{ $user->role }}">
                    @endif

                    {{-- Status --}}
                    <div class="mb-4">
                        <x-input-label for="status" value="Status" />
                        <select
                            id="status"
                            name="status"
                            class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1"
                            required
                        >
                            <option value="" class="bg-surface" class="bg-surface">— Select Status —</option>
                            @foreach (['active', 'inactive', 'resigned'] as $status)
                                <option
                                    value="{{ $status }}"
                                    {{ old('status', $user->status) === $status ? 'selected' : '' }}
                                    class="bg-surface"
                                >
                                    {{ ucfirst($status) }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>

                    {{-- Assigned Admin --}}
                    <div class="mb-6">
                        <x-input-label for="admin_id" value="Assigned Admin (optional)" />
                        <select
                            id="admin_id"
                            name="admin_id"
                            class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1"
                        >
                            <option value="" class="bg-surface" class="bg-surface">— No Admin —</option>
                            @foreach ($admins as $admin)
                                <option
                                    value="{{ $admin->id }}"
                                    {{ old('admin_id', $user->admin_id) == $admin->id ? 'selected' : '' }}
                                    class="bg-surface"
                                >
                                    {{ $admin->name }} ({{ $admin->employee_id }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('admin_id')" class="mt-2" />
                    </div>

                    {{-- Assigned Manager --}}
                    @if(auth()->user()->role === 'admin')
                        <div class="mb-6">
                            <x-input-label for="manager_id" value="Assigned Manager (optional)" />
                            <select
                                id="manager_id"
                                name="manager_id"
                                class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1"
                            >
                                <option value="" class="bg-surface" class="bg-surface">— No Manager —</option>
                                @foreach ($managers as $manager)
                                    <option
                                        value="{{ $manager->id }}"
                                        {{ old('manager_id', $user->manager_id) == $manager->id ? 'selected' : '' }}
                                        class="bg-surface"
                                    >
                                        {{ $manager->name }} ({{ $manager->employee_id }})
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('manager_id')" class="mt-2" />
                        </div>
                    @else
                        <input type="hidden" name="manager_id" value="{{ $user->manager_id }}">
                    @endif

                    <!-- Additional Profile Information Sections -->
                    <div class="mt-8 space-y-4 mb-6">
                        <h3 class="text-lg font-semibold text-brass border-b border-hairline pb-2 mb-4 font-display">Additional Profile Information</h3>
                        
                        <!-- 1. Personal Section -->
                        <details class="group bg-surface rounded-lg p-4 border border-hairline" open>
                            <summary class="font-semibold text-brass cursor-pointer focus:outline-none flex items-center justify-between select-none font-display">
                                <span>Section "Personal"</span>
                                <span class="transition group-open:rotate-180">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </span>
                            </summary>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <div>
                                    <label for="father_name" class="block text-sm font-medium text-vellum-muted">Father's Name</label>
                                    <input type="text" name="father_name" id="father_name" value="{{ old('father_name', $user->employeeProfile?->father_name) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('father_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="mother_name" class="block text-sm font-medium text-vellum-muted">Mother's Name</label>
                                    <input type="text" name="mother_name" id="mother_name" value="{{ old('mother_name', $user->employeeProfile?->mother_name) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('mother_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="gender" class="block text-sm font-medium text-vellum-muted">Gender</label>
                                    <select name="gender" id="gender" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                         <option value="" class="bg-surface">Select Gender</option>
                                         <option value="Male" {{ old('gender', $user->employeeProfile?->gender) === 'Male' ? 'selected' : '' }}>Male</option>
                                         <option value="Female" {{ old('gender', $user->employeeProfile?->gender) === 'Female' ? 'selected' : '' }}>Female</option>
                                         <option value="Other" {{ old('gender', $user->employeeProfile?->gender) === 'Other' ? 'selected' : '' }}>Other</option>
                                     </select>
                                    @error('gender') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="date_of_birth" class="block text-sm font-medium text-vellum-muted">Date of Birth</label>
                                    <input type="date" name="date_of_birth" id="date_of_birth" value="{{ old('date_of_birth', $user->employeeProfile?->date_of_birth?->format('Y-m-d')) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('date_of_birth') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="marital_status" class="block text-sm font-medium text-vellum-muted">Marital Status</label>
                                    <input type="text" name="marital_status" id="marital_status" value="{{ old('marital_status', $user->employeeProfile?->marital_status) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('marital_status') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="date_of_marriage" class="block text-sm font-medium text-vellum-muted">Date of Marriage</label>
                                    <input type="date" name="date_of_marriage" id="date_of_marriage" value="{{ old('date_of_marriage', $user->employeeProfile?->date_of_marriage?->format('Y-m-d')) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('date_of_marriage') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="nationality" class="block text-sm font-medium text-vellum-muted">Nationality</label>
                                    <input type="text" name="nationality" id="nationality" value="{{ old('nationality', $user->employeeProfile?->nationality) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('nationality') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="blood_group" class="block text-sm font-medium text-vellum-muted">Blood Group</label>
                                    <input type="text" name="blood_group" id="blood_group" value="{{ old('blood_group', $user->employeeProfile?->blood_group) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('blood_group') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="personal_email" class="block text-sm font-medium text-vellum-muted">Personal Email</label>
                                    <input type="email" name="personal_email" id="personal_email" value="{{ old('personal_email', $user->employeeProfile?->personal_email) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('personal_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="mobile_no" class="block text-sm font-medium text-vellum-muted">Mobile No</label>
                                    <input type="text" name="mobile_no" id="mobile_no" value="{{ old('mobile_no', $user->employeeProfile?->mobile_no) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('mobile_no') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </details>

                        <!-- 2. Government IDs Section -->
                        <details class="group bg-surface rounded-lg p-4 border border-hairline">
                            <summary class="font-semibold text-brass cursor-pointer focus:outline-none flex items-center justify-between select-none font-display">
                                <span>Section "Government IDs"</span>
                                <span class="transition group-open:rotate-180">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </span>
                            </summary>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <div>
                                    <label for="pf_uan" class="block text-sm font-medium text-vellum-muted">PF UAN</label>
                                    <input type="text" name="pf_uan" id="pf_uan" value="{{ old('pf_uan', $user->employeeProfile?->pf_uan) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('pf_uan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="passport_no" class="block text-sm font-medium text-vellum-muted">Passport No</label>
                                    <input type="text" name="passport_no" id="passport_no" value="{{ old('passport_no', $user->employeeProfile?->passport_no) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('passport_no') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="aadhar_card" class="block text-sm font-medium text-vellum-muted">Aadhar Card</label>
                                    <input type="text" name="aadhar_card" id="aadhar_card" value="{{ old('aadhar_card', $user->employeeProfile?->aadhar_card) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('aadhar_card') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="pan" class="block text-sm font-medium text-vellum-muted">PAN</label>
                                    <input type="text" name="pan" id="pan" value="{{ old('pan', $user->employeeProfile?->pan) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('pan') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="pf_no" class="block text-sm font-medium text-vellum-muted">PF No</label>
                                    <input type="text" name="pf_no" id="pf_no" value="{{ old('pf_no', $user->employeeProfile?->pf_no) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('pf_no') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="esi_number" class="block text-sm font-medium text-vellum-muted">ESI Number</label>
                                    <input type="text" name="esi_number" id="esi_number" value="{{ old('esi_number', $user->employeeProfile?->esi_number) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('esi_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="date_of_gratuity" class="block text-sm font-medium text-vellum-muted">Date of Gratuity</label>
                                    <input type="date" name="date_of_gratuity" id="date_of_gratuity" value="{{ old('date_of_gratuity', $user->employeeProfile?->date_of_gratuity?->format('Y-m-d')) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('date_of_gratuity') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </details>

                        <!-- 3. Employment Section -->
                        <details class="group bg-surface rounded-lg p-4 border border-hairline">
                            <summary class="font-semibold text-brass cursor-pointer focus:outline-none flex items-center justify-between select-none font-display">
                                <span>Section "Employment"</span>
                                <span class="transition group-open:rotate-180">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </span>
                            </summary>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <div>
                                    <label for="payroll_type" class="block text-sm font-medium text-vellum-muted">Payroll Type</label>
                                    <input type="text" name="payroll_type" id="payroll_type" value="{{ old('payroll_type', $user->employeeProfile?->payroll_type) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('payroll_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="contract_end_date" class="block text-sm font-medium text-vellum-muted">Contract End Date</label>
                                    <input type="date" name="contract_end_date" id="contract_end_date" value="{{ old('contract_end_date', $user->employeeProfile?->contract_end_date?->format('Y-m-d')) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('contract_end_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="office_landline" class="block text-sm font-medium text-vellum-muted">Office Landline</label>
                                    <input type="text" name="office_landline" id="office_landline" value="{{ old('office_landline', $user->employeeProfile?->office_landline) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('office_landline') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="leave_rule" class="block text-sm font-medium text-vellum-muted">Leave Rule</label>
                                    <input type="text" name="leave_rule" id="leave_rule" value="{{ old('leave_rule', $user->employeeProfile?->leave_rule) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('leave_rule') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="shift" class="block text-sm font-medium text-vellum-muted">Shift</label>
                                    <input type="text" name="shift" id="shift" value="{{ old('shift', $user->employeeProfile?->shift) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('shift') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="designation" class="block text-sm font-medium text-vellum-muted">Designation</label>
                                    <input type="text" name="designation" id="designation" value="{{ old('designation', $user->employeeProfile?->designation) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('designation') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="grade" class="block text-sm font-medium text-vellum-muted">Grade</label>
                                    <input type="text" name="grade" id="grade" value="{{ old('grade', $user->employeeProfile?->grade) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('grade') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="employee_type" class="block text-sm font-medium text-vellum-muted">Employee Type</label>
                                    <input type="text" name="employee_type" id="employee_type" value="{{ old('employee_type', $user->employeeProfile?->employee_type) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('employee_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="company" class="block text-sm font-medium text-vellum-muted">Company</label>
                                    <input type="text" name="company" id="company" value="{{ old('company', $user->employeeProfile?->company) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('company') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="location" class="block text-sm font-medium text-vellum-muted">Location</label>
                                    <input type="text" name="location" id="location" value="{{ old('location', $user->employeeProfile?->location) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('location') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="biometric_id" class="block text-sm font-medium text-vellum-muted">Biometric ID</label>
                                    <input type="text" name="biometric_id" id="biometric_id" value="{{ old('biometric_id', $user->employeeProfile?->biometric_id) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('biometric_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="hiring_source" class="block text-sm font-medium text-vellum-muted">Hiring Source</label>
                                    <input type="text" name="hiring_source" id="hiring_source" value="{{ old('hiring_source', $user->employeeProfile?->hiring_source) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('hiring_source') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="source_of_verification" class="block text-sm font-medium text-vellum-muted">Source of Verification</label>
                                    <input type="text" name="source_of_verification" id="source_of_verification" value="{{ old('source_of_verification', $user->employeeProfile?->source_of_verification) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('source_of_verification') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="city_type" class="block text-sm font-medium text-vellum-muted">City Type</label>
                                    <input type="text" name="city_type" id="city_type" value="{{ old('city_type', $user->employeeProfile?->city_type) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('city_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="notice_days" class="block text-sm font-medium text-vellum-muted">Notice Days</label>
                                    <input type="number" name="notice_days" id="notice_days" value="{{ old('notice_days', $user->employeeProfile?->notice_days) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('notice_days') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="state_name" class="block text-sm font-medium text-vellum-muted">State Name</label>
                                    <input type="text" name="state_name" id="state_name" value="{{ old('state_name', $user->employeeProfile?->state_name) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('state_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </details>

                        <!-- 4. Current Address Section -->
                        <details class="group bg-surface rounded-lg p-4 border border-hairline">
                            <summary class="font-semibold text-brass cursor-pointer focus:outline-none flex items-center justify-between select-none font-display">
                                <span>Section "Current Address"</span>
                                <span class="transition group-open:rotate-180">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </span>
                            </summary>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <div>
                                    <label for="current_address1" class="block text-sm font-medium text-vellum-muted">Current Address Line 1</label>
                                    <input type="text" name="current_address1" id="current_address1" value="{{ old('current_address1', $user->employeeProfile?->current_address1) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('current_address1') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="current_address2" class="block text-sm font-medium text-vellum-muted">Current Address Line 2</label>
                                    <input type="text" name="current_address2" id="current_address2" value="{{ old('current_address2', $user->employeeProfile?->current_address2) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('current_address2') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="current_country" class="block text-sm font-medium text-vellum-muted">Current Country</label>
                                    <input type="text" name="current_country" id="current_country" value="{{ old('current_country', $user->employeeProfile?->current_country) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('current_country') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="current_state" class="block text-sm font-medium text-vellum-muted">Current State</label>
                                    <input type="text" name="current_state" id="current_state" value="{{ old('current_state', $user->employeeProfile?->current_state) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('current_state') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="current_city" class="block text-sm font-medium text-vellum-muted">Current City</label>
                                    <input type="text" name="current_city" id="current_city" value="{{ old('current_city', $user->employeeProfile?->current_city) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('current_city') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="current_zip" class="block text-sm font-medium text-vellum-muted">Current Zip Code</label>
                                    <input type="text" name="current_zip" id="current_zip" value="{{ old('current_zip', $user->employeeProfile?->current_zip) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('current_zip') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </details>

                        <!-- 5. Permanent Address Section -->
                        <details class="group bg-surface rounded-lg p-4 border border-hairline">
                            <summary class="font-semibold text-brass cursor-pointer focus:outline-none flex items-center justify-between select-none font-display">
                                <span>Section "Permanent Address"</span>
                                <span class="transition group-open:rotate-180">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </span>
                            </summary>
                            <div class="mt-4">
                                <label class="inline-flex items-center mb-4">
                                    <input type="checkbox" name="same_as_current_address" id="same_as_current_address" value="1" {{ old('same_as_current_address', $user->employeeProfile?->same_as_current_address) ? 'checked' : '' }} class="rounded bg-surface-raised border-hairline text-brass focus:ring-brass focus:ring-offset-canvas focus:ring-offset-2">
                                    <span class="ml-2 text-sm text-vellum-muted">Same as Current Address</span>
                                </label>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="permanent_address1" class="block text-sm font-medium text-vellum-muted">Permanent Address Line 1</label>
                                        <input type="text" name="permanent_address1" id="permanent_address1" value="{{ old('permanent_address1', $user->employeeProfile?->permanent_address1) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                        @error('permanent_address1') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="permanent_address2" class="block text-sm font-medium text-vellum-muted">Permanent Address Line 2</label>
                                        <input type="text" name="permanent_address2" id="permanent_address2" value="{{ old('permanent_address2', $user->employeeProfile?->permanent_address2) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                        @error('permanent_address2') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="permanent_country" class="block text-sm font-medium text-vellum-muted">Permanent Country</label>
                                        <input type="text" name="permanent_country" id="permanent_country" value="{{ old('permanent_country', $user->employeeProfile?->permanent_country) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                        @error('permanent_country') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="permanent_state" class="block text-sm font-medium text-vellum-muted">Permanent State</label>
                                        <input type="text" name="permanent_state" id="permanent_state" value="{{ old('permanent_state', $user->employeeProfile?->permanent_state) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                        @error('permanent_state') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="permanent_city" class="block text-sm font-medium text-vellum-muted">Permanent City</label>
                                        <input type="text" name="permanent_city" id="permanent_city" value="{{ old('permanent_city', $user->employeeProfile?->permanent_city) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                        @error('permanent_city') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="permanent_zip" class="block text-sm font-medium text-vellum-muted">Permanent Zip Code</label>
                                        <input type="text" name="permanent_zip" id="permanent_zip" value="{{ old('permanent_zip', $user->employeeProfile?->permanent_zip) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                        @error('permanent_zip') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>
                        </details>

                        <!-- 6. Bank Details Section -->
                        <details class="group bg-surface rounded-lg p-4 border border-hairline">
                            <summary class="font-semibold text-brass cursor-pointer focus:outline-none flex items-center justify-between select-none font-display">
                                <span>Section "Bank Details"</span>
                                <span class="transition group-open:rotate-180">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </span>
                            </summary>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <div>
                                    <label for="payment_type" class="block text-sm font-medium text-vellum-muted">Payment Type</label>
                                    <input type="text" name="payment_type" id="payment_type" value="{{ old('payment_type', $user->employeeProfile?->payment_type) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('payment_type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="bank_name" class="block text-sm font-medium text-vellum-muted">Bank Name</label>
                                    <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name', $user->employeeProfile?->bank_name) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('bank_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="account_holder_name" class="block text-sm font-medium text-vellum-muted">Account Holder Name</label>
                                    <input type="text" name="account_holder_name" id="account_holder_name" value="{{ old('account_holder_name', $user->employeeProfile?->account_holder_name) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('account_holder_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="account_no" class="block text-sm font-medium text-vellum-muted">Account No</label>
                                    <input type="text" name="account_no" id="account_no" value="{{ old('account_no', $user->employeeProfile?->account_no) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('account_no') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="ifsc_code" class="block text-sm font-medium text-vellum-muted">IFSC Code</label>
                                    <input type="text" name="ifsc_code" id="ifsc_code" value="{{ old('ifsc_code', $user->employeeProfile?->ifsc_code) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('ifsc_code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </details>

                        <!-- 7. Emergency Contact Section -->
                        <details class="group bg-surface rounded-lg p-4 border border-hairline">
                            <summary class="font-semibold text-brass cursor-pointer focus:outline-none flex items-center justify-between select-none font-display">
                                <span>Section "Emergency Contact"</span>
                                <span class="transition group-open:rotate-180">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </span>
                            </summary>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <div>
                                    <label for="emergency_name" class="block text-sm font-medium text-vellum-muted">Emergency Name</label>
                                    <input type="text" name="emergency_name" id="emergency_name" value="{{ old('emergency_name', $user->employeeProfile?->emergency_name) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('emergency_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="emergency_relationship" class="block text-sm font-medium text-vellum-muted">Emergency Relationship</label>
                                    <input type="text" name="emergency_relationship" id="emergency_relationship" value="{{ old('emergency_relationship', $user->employeeProfile?->emergency_relationship) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('emergency_relationship') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="emergency_address" class="block text-sm font-medium text-vellum-muted">Emergency Address</label>
                                    <input type="text" name="emergency_address" id="emergency_address" value="{{ old('emergency_address', $user->employeeProfile?->emergency_address) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('emergency_address') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="emergency_email" class="block text-sm font-medium text-vellum-muted">Emergency Email</label>
                                    <input type="email" name="emergency_email" id="emergency_email" value="{{ old('emergency_email', $user->employeeProfile?->emergency_email) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('emergency_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="emergency_mobile" class="block text-sm font-medium text-vellum-muted">Emergency Mobile</label>
                                    <input type="text" name="emergency_mobile" id="emergency_mobile" value="{{ old('emergency_mobile', $user->employeeProfile?->emergency_mobile) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('emergency_mobile') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </details>

                        <!-- 8. Education Section -->
                        <details class="group bg-surface rounded-lg p-4 border border-hairline">
                            <summary class="font-semibold text-brass cursor-pointer focus:outline-none flex items-center justify-between select-none font-display">
                                <span>Section "Education"</span>
                                <span class="transition group-open:rotate-180">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </span>
                            </summary>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <div>
                                    <label for="degree_name" class="block text-sm font-medium text-vellum-muted">Degree Name</label>
                                    <input type="text" name="degree_name" id="degree_name" value="{{ old('degree_name', $user->employeeProfile?->degree_name) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('degree_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="institution_name" class="block text-sm font-medium text-vellum-muted">Institution Name</label>
                                    <input type="text" name="institution_name" id="institution_name" value="{{ old('institution_name', $user->employeeProfile?->institution_name) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('institution_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="passing_year" class="block text-sm font-medium text-vellum-muted">Passing Year</label>
                                    <input type="text" name="passing_year" id="passing_year" value="{{ old('passing_year', $user->employeeProfile?->passing_year) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('passing_year') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="percentage" class="block text-sm font-medium text-vellum-muted">Percentage</label>
                                    <input type="text" name="percentage" id="percentage" value="{{ old('percentage', $user->employeeProfile?->percentage) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('percentage') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </details>

                        <!-- 9. Previous Employment Section -->
                        <details class="group bg-surface rounded-lg p-4 border border-hairline">
                            <summary class="font-semibold text-brass cursor-pointer focus:outline-none flex items-center justify-between select-none font-display">
                                <span>Section "Previous Employment"</span>
                                <span class="transition group-open:rotate-180">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </span>
                            </summary>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <div>
                                    <label for="previous_company_name" class="block text-sm font-medium text-vellum-muted">Previous Company Name</label>
                                    <input type="text" name="previous_company_name" id="previous_company_name" value="{{ old('previous_company_name', $user->employeeProfile?->previous_company_name) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('previous_company_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="previous_job_title" class="block text-sm font-medium text-vellum-muted">Previous Job Title</label>
                                    <input type="text" name="previous_job_title" id="previous_job_title" value="{{ old('previous_job_title', $user->employeeProfile?->previous_job_title) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('previous_job_title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="previous_from_date" class="block text-sm font-medium text-vellum-muted">From Date</label>
                                    <input type="date" name="previous_from_date" id="previous_from_date" value="{{ old('previous_from_date', $user->employeeProfile?->previous_from_date?->format('Y-m-d')) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('previous_from_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="previous_to_date" class="block text-sm font-medium text-vellum-muted">To Date</label>
                                    <input type="date" name="previous_to_date" id="previous_to_date" value="{{ old('previous_to_date', $user->employeeProfile?->previous_to_date?->format('Y-m-d')) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('previous_to_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </details>

                        <!-- 10. Tenure Section -->
                        <details class="group bg-surface rounded-lg p-4 border border-hairline">
                            <summary class="font-semibold text-brass cursor-pointer focus:outline-none flex items-center justify-between select-none font-display">
                                <span>Section "Tenure"</span>
                                <span class="transition group-open:rotate-180">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </span>
                            </summary>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <div>
                                    <label for="probation_period" class="block text-sm font-medium text-vellum-muted">Probation Period</label>
                                    <input type="text" name="probation_period" id="probation_period" value="{{ old('probation_period', $user->employeeProfile?->probation_period) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('probation_period') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="probation_confirm_date" class="block text-sm font-medium text-vellum-muted">Probation Confirm Date</label>
                                    <input type="date" name="probation_confirm_date" id="probation_confirm_date" value="{{ old('probation_confirm_date', $user->employeeProfile?->probation_confirm_date?->format('Y-m-d')) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('probation_confirm_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="separation_date" class="block text-sm font-medium text-vellum-muted">Separation Date</label>
                                    <input type="date" name="separation_date" id="separation_date" value="{{ old('separation_date', $user->employeeProfile?->separation_date?->format('Y-m-d')) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('separation_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="last_working_day" class="block text-sm font-medium text-vellum-muted">Last Working Day (LWD)</label>
                                    <input type="date" name="last_working_day" id="last_working_day" value="{{ old('last_working_day', $user->employeeProfile?->last_working_day?->format('Y-m-d')) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('last_working_day') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="previous_year_experience" class="block text-sm font-medium text-vellum-muted">Previous Experience (Years)</label>
                                    <input type="text" name="previous_year_experience" id="previous_year_experience" value="{{ old('previous_year_experience', $user->employeeProfile?->previous_year_experience) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                    @error('previous_year_experience') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                 </div>
                                 <div>
                                     <label for="years_completed" class="block text-sm font-medium text-vellum-muted">Years Completed</label>
                                     <input type="text" name="years_completed" id="years_completed" value="{{ old('years_completed', $user->employeeProfile?->years_completed) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
                                     @error('years_completed') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                 </div>
                                 <div>
                                     <label for="overall_year_experience" class="block text-sm font-medium text-vellum-muted">Overall Experience (Years)</label>
                                     <input type="text" name="overall_year_experience" id="overall_year_experience" value="{{ old('overall_year_experience', $user->employeeProfile?->overall_year_experience) }}" class="mt-1 block w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1 text-sm">
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
                                    permField.classList.add('bg-surface/50', 'cursor-not-allowed');
                                });
                            } else {
                                permanentFields.forEach(id => {
                                    const permField = document.getElementById(id);
                                    permField.disabled = false;
                                    permField.classList.remove('bg-surface/50', 'cursor-not-allowed');
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

                    {{-- Actions --}}
                    <div class="flex items-center gap-4">
                        <x-primary-button>Save Changes</x-primary-button>
                        
                        <a
                            href="{{ route('employees.index') }}"
                            class="text-sm text-vellum-muted hover:text-brass transition duration-150 underline"
                        >
                            Cancel
                        </a>
                    </div>

                </form>
            </div>
        </div>
    </div>
</x-app-layout>