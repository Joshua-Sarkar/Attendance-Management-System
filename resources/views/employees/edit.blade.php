@php
    $year = now()->year;
    $birthdayCredit = \App\Models\LeaveCredit::where('user_id', $user->id)
        ->where('source_identifier', "birthday_{$year}")
        ->first();
    $birthdayCreditExists = $birthdayCredit !== null;
    $birthdayBalance = $birthdayCreditExists ? (float) ($birthdayCredit->amount - $birthdayCredit->used_amount) : 0.00;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1.5">
            <h1 class="font-display font-medium text-[32px] tracking-wide text-vellum">Edit Workforce Member</h1>
            <div class="text-[13px] text-vellum-muted mt-1.5 tracking-wide">
                Update personnel profile for {{ $user->name }} · ID: <span class="font-mono text-brass font-semibold">{{ $user->employee_id }}</span>
            </div>
        </div>
    </x-slot>

    <div class="w-full">
        <!-- Validation Error Alert at Top -->
        @if ($errors->any())
            <div class="rounded bg-burgundy-bg border border-hairline text-burgundy px-4 py-3 text-sm mb-6">
                <div class="font-semibold uppercase tracking-wider text-xs mb-1">Please correct the following errors:</div>
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('employees.update', $user) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <!-- Responsive Two-Column Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
                
                <!-- ================= LEFT COLUMN ================= -->
                <div class="space-y-6">

                    <!-- 1. IDENTITY & PERSONAL DETAILS CARD -->
                    <div class="bg-surface border border-hairline rounded p-6 shadow-sm">
                        <h3 class="text-sm font-semibold text-brass uppercase tracking-wider mb-4 font-display border-b border-hairline pb-2">Identity & Personal Details</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Profile Photo Upload -->
                            <div class="md:col-span-2 flex items-center gap-4 mb-2">
                                @if($user->profile_photo_path)
                                    <img src="{{ asset('storage/' . $user->profile_photo_path) }}" class="h-16 w-16 rounded object-cover border border-brass" alt="{{ $user->name }}">
                                @else
                                    <div class="h-16 w-16 rounded bg-brass flex items-center justify-center text-canvas text-xl font-display font-medium border border-brass">
                                        {{ substr($user->name, 0, 2) }}
                                    </div>
                                @endif
                                <div>
                                    <label for="profile_photo" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1">
                                        Profile Photo
                                    </label>
                                    <input
                                        type="file"
                                        name="profile_photo"
                                        id="profile_photo"
                                        class="text-xs text-vellum-muted file:mr-4 file:py-1.5 file:px-3 file:rounded file:border file:border-brass/30 file:text-xs file:font-semibold file:bg-brass/10 file:text-brass hover:file:bg-brass/20 cursor-pointer"
                                    >
                                    <p class="text-[11px] text-vellum-faint mt-1">PNG, JPG, JPEG up to 2MB</p>
                                    @error('profile_photo')
                                        <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Employee ID -->
                            <div>
                                <label for="employee_id" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">
                                    Employee ID
                                </label>
                                <input
                                    type="text"
                                    name="employee_id"
                                    id="employee_id"
                                    value="{{ old('employee_id', $user->employee_id) }}"
                                    required
                                    class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5"
                                >
                                @error('employee_id')
                                    <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">
                                    Full Name
                                </label>
                                <input
                                    type="text"
                                    name="name"
                                    id="name"
                                    value="{{ old('name', $user->name) }}"
                                    required
                                    class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5"
                                >
                                @error('name')
                                    <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Collapsible Personal Information Section -->
                        <div class="mt-4">
                            <details class="group bg-surface rounded-md p-4 border border-hairline" open>
                                <summary class="font-semibold text-vellum font-display cursor-pointer focus:outline-none flex items-center justify-between select-none text-xs uppercase tracking-wider">
                                    <span>Personal Information Details</span>
                                    <span class="transition group-open:rotate-180">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </span>
                                </summary>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label for="father_name" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5 font-sans">Father's Name</label>
                                        <input type="text" name="father_name" id="father_name" value="{{ old('father_name', $user->employeeProfile?->father_name) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('father_name') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="mother_name" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5 font-sans">Mother's Name</label>
                                        <input type="text" name="mother_name" id="mother_name" value="{{ old('mother_name', $user->employeeProfile?->mother_name) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('mother_name') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="gender" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5 font-sans">Gender</label>
                                        <select name="gender" id="gender" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                            <option value="">Select Gender</option>
                                            <option value="Male" {{ old('gender', $user->employeeProfile?->gender) === 'Male' ? 'selected' : '' }}>Male</option>
                                            <option value="Female" {{ old('gender', $user->employeeProfile?->gender) === 'Female' ? 'selected' : '' }}>Female</option>
                                            <option value="Other" {{ old('gender', $user->employeeProfile?->gender) === 'Other' ? 'selected' : '' }}>Other</option>
                                        </select>
                                        @error('gender') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="date_of_birth" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5 font-sans">Date of Birth</label>
                                        <input type="date" name="date_of_birth" id="date_of_birth" value="{{ old('date_of_birth', $user->employeeProfile?->date_of_birth?->format('Y-m-d')) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('date_of_birth') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="marital_status" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5 font-sans">Marital Status</label>
                                        <input type="text" name="marital_status" id="marital_status" value="{{ old('marital_status', $user->employeeProfile?->marital_status) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('marital_status') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="date_of_marriage" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5 font-sans">Date of Marriage</label>
                                        <input type="date" name="date_of_marriage" id="date_of_marriage" value="{{ old('date_of_marriage', $user->employeeProfile?->date_of_marriage?->format('Y-m-d')) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('date_of_marriage') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="nationality" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5 font-sans">Nationality</label>
                                        <input type="text" name="nationality" id="nationality" value="{{ old('nationality', $user->employeeProfile?->nationality) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('nationality') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="blood_group" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5 font-sans">Blood Group</label>
                                        <input type="text" name="blood_group" id="blood_group" value="{{ old('blood_group', $user->employeeProfile?->blood_group) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('blood_group') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </details>
                        </div>

                        <!-- Collapsible Government IDs Section -->
                        <div class="mt-4">
                            <details class="group bg-surface rounded-md p-4 border border-hairline">
                                <summary class="font-semibold text-vellum font-display cursor-pointer focus:outline-none flex items-center justify-between select-none text-xs uppercase tracking-wider">
                                    <span>Government IDs</span>
                                    <span class="transition group-open:rotate-180">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </span>
                                </summary>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label for="pf_uan" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">PF UAN</label>
                                        <input type="text" name="pf_uan" id="pf_uan" value="{{ old('pf_uan', $user->employeeProfile?->pf_uan) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('pf_uan') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="passport_no" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Passport No</label>
                                        <input type="text" name="passport_no" id="passport_no" value="{{ old('passport_no', $user->employeeProfile?->passport_no) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('passport_no') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="aadhar_card" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Aadhar Card</label>
                                        <input type="text" name="aadhar_card" id="aadhar_card" value="{{ old('aadhar_card', $user->employeeProfile?->aadhar_card) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('aadhar_card') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="pan" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">PAN</label>
                                        <input type="text" name="pan" id="pan" value="{{ old('pan', $user->employeeProfile?->pan) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('pan') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="pf_no" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">PF No</label>
                                        <input type="text" name="pf_no" id="pf_no" value="{{ old('pf_no', $user->employeeProfile?->pf_no) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('pf_no') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="esi_number" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">ESI Number</label>
                                        <input type="text" name="esi_number" id="esi_number" value="{{ old('esi_number', $user->employeeProfile?->esi_number) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('esi_number') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="date_of_gratuity" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Date of Gratuity</label>
                                        <input type="date" name="date_of_gratuity" id="date_of_gratuity" value="{{ old('date_of_gratuity', $user->employeeProfile?->date_of_gratuity?->format('Y-m-d')) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('date_of_gratuity') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </details>
                        </div>
                    </div>

                    <!-- 2. EMPLOYMENT CARD -->
                    <div class="bg-surface border border-hairline rounded p-6 shadow-sm">
                        <h3 class="text-sm font-semibold text-brass uppercase tracking-wider mb-4 font-display border-b border-hairline pb-2">Employment</h3>
                        
                        <div>
                            <label for="department_id" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">
                                Department
                            </label>
                            <select
                                name="department_id"
                                id="department_id"
                                required
                                class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5"
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
                            @error('department_id')
                                <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Collapsible Employment Details Section -->
                        <div class="mt-4">
                            <details class="group bg-surface rounded-md p-4 border border-hairline">
                                <summary class="font-semibold text-vellum font-display cursor-pointer focus:outline-none flex items-center justify-between select-none text-xs uppercase tracking-wider">
                                    <span>Employment Details</span>
                                    <span class="transition group-open:rotate-180">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </span>
                                </summary>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label for="designation" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Designation</label>
                                        <input type="text" name="designation" id="designation" value="{{ old('designation', $user->employeeProfile?->designation) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('designation') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="grade" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Grade</label>
                                        <input type="text" name="grade" id="grade" value="{{ old('grade', $user->employeeProfile?->grade) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('grade') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="employee_type" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Employee Type</label>
                                        <input type="text" name="employee_type" id="employee_type" value="{{ old('employee_type', $user->employeeProfile?->employee_type) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('employee_type') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="employee_category" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Employee Category</label>
                                        <input type="text" name="employee_category" id="employee_category" value="{{ old('employee_category', $user->employeeProfile?->employee_category) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('employee_category') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="company" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Company</label>
                                        <input type="text" name="company" id="company" value="{{ old('company', $user->employeeProfile?->company) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('company') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="location" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Location</label>
                                        <input type="text" name="location" id="location" value="{{ old('location', $user->employeeProfile?->location) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('location') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="biometric_id" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Biometric ID</label>
                                        <input type="text" name="biometric_id" id="biometric_id" value="{{ old('biometric_id', $user->employeeProfile?->biometric_id) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('biometric_id') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="hiring_source" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Hiring Source</label>
                                        <input type="text" name="hiring_source" id="hiring_source" value="{{ old('hiring_source', $user->employeeProfile?->hiring_source) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('hiring_source') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="source_of_verification" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Source of Verification</label>
                                        <input type="text" name="source_of_verification" id="source_of_verification" value="{{ old('source_of_verification', $user->employeeProfile?->source_of_verification) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('source_of_verification') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="city_type" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">City Type</label>
                                        <input type="text" name="city_type" id="city_type" value="{{ old('city_type', $user->employeeProfile?->city_type) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('city_type') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="notice_days" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Notice Days</label>
                                        <input type="number" name="notice_days" id="notice_days" value="{{ old('notice_days', $user->employeeProfile?->notice_days) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('notice_days') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="state_name" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">State Name</label>
                                        <input type="text" name="state_name" id="state_name" value="{{ old('state_name', $user->employeeProfile?->state_name) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('state_name') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="payroll_type" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5 font-sans">Payroll Type</label>
                                        <input type="text" name="payroll_type" id="payroll_type" value="{{ old('payroll_type', $user->employeeProfile?->payroll_type) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('payroll_type') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="leave_rule" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5 font-sans">Leave Rule</label>
                                        <input type="text" name="leave_rule" id="leave_rule" value="{{ old('leave_rule', $user->employeeProfile?->leave_rule) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('leave_rule') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="md:col-span-2">
                                        <label for="shift" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5 font-sans">Shift</label>
                                        <input type="text" name="shift" id="shift" value="{{ old('shift', $user->employeeProfile?->shift) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('shift') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </details>
                        </div>

                        <!-- Collapsible Education Section -->
                        <div class="mt-4">
                            <details class="group bg-surface rounded-md p-4 border border-hairline">
                                <summary class="font-semibold text-vellum font-display cursor-pointer focus:outline-none flex items-center justify-between select-none text-xs uppercase tracking-wider">
                                    <span>Education Details</span>
                                    <span class="transition group-open:rotate-180">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </span>
                                </summary>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label for="degree_name" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Degree Name</label>
                                        <input type="text" name="degree_name" id="degree_name" value="{{ old('degree_name', $user->employeeProfile?->degree_name) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('degree_name') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="institution_name" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Institution Name</label>
                                        <input type="text" name="institution_name" id="institution_name" value="{{ old('institution_name', $user->employeeProfile?->institution_name) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('institution_name') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="passing_year" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Passing Year</label>
                                        <input type="text" name="passing_year" id="passing_year" value="{{ old('passing_year', $user->employeeProfile?->passing_year) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('passing_year') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="percentage" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Percentage</label>
                                        <input type="text" name="percentage" id="percentage" value="{{ old('percentage', $user->employeeProfile?->percentage) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('percentage') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </details>
                        </div>
                    </div>

                    <!-- 3. CONTACT DETAILS CARD -->
                    <div class="bg-surface border border-hairline rounded p-6 shadow-sm">
                        <h3 class="text-sm font-semibold text-brass uppercase tracking-wider mb-4 font-display border-b border-hairline pb-2">Contact Details</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="phone" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">
                                    Phone Number
                                </label>
                                <input
                                    type="text"
                                    name="phone"
                                    id="phone"
                                    value="{{ old('phone', $user->phone) }}"
                                    class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5"
                                >
                                @error('phone')
                                    <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="email" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">
                                    Email Address
                                </label>
                                <input
                                    type="email"
                                    name="email"
                                    id="email"
                                    value="{{ old('email', $user->email) }}"
                                    required
                                    class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5"
                                >
                                @error('email')
                                    <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="personal_email" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Personal Email</label>
                                <input type="email" name="personal_email" id="personal_email" value="{{ old('personal_email', $user->employeeProfile?->personal_email) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                @error('personal_email') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label for="mobile_no" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Mobile No</label>
                                <input type="text" name="mobile_no" id="mobile_no" value="{{ old('mobile_no', $user->employeeProfile?->mobile_no) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                @error('mobile_no') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label for="office_landline" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Office Landline</label>
                                <input type="text" name="office_landline" id="office_landline" value="{{ old('office_landline', $user->employeeProfile?->office_landline) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                @error('office_landline') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <!-- Collapsible Current Address -->
                        <div class="mt-4">
                            <details class="group bg-surface rounded-md p-4 border border-hairline">
                                <summary class="font-semibold text-vellum font-display cursor-pointer focus:outline-none flex items-center justify-between select-none text-xs uppercase tracking-wider">
                                    <span>Current Address</span>
                                    <span class="transition group-open:rotate-180">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </span>
                                </summary>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label for="current_address1" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Address Line 1</label>
                                        <input type="text" name="current_address1" id="current_address1" value="{{ old('current_address1', $user->employeeProfile?->current_address1) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('current_address1') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="current_address2" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Address Line 2</label>
                                        <input type="text" name="current_address2" id="current_address2" value="{{ old('current_address2', $user->employeeProfile?->current_address2) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('current_address2') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="current_country" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Country</label>
                                        <input type="text" name="current_country" id="current_country" value="{{ old('current_country', $user->employeeProfile?->current_country) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('current_country') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="current_state" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">State</label>
                                        <input type="text" name="current_state" id="current_state" value="{{ old('current_state', $user->employeeProfile?->current_state) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('current_state') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="current_city" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">City</label>
                                        <input type="text" name="current_city" id="current_city" value="{{ old('current_city', $user->employeeProfile?->current_city) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('current_city') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="current_zip" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Zip Code</label>
                                        <input type="text" name="current_zip" id="current_zip" value="{{ old('current_zip', $user->employeeProfile?->current_zip) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('current_zip') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </details>
                        </div>

                        <!-- Collapsible Permanent Address -->
                        <div class="mt-4">
                            <details class="group bg-surface rounded-md p-4 border border-hairline">
                                <summary class="font-semibold text-vellum font-display cursor-pointer focus:outline-none flex items-center justify-between select-none text-xs uppercase tracking-wider">
                                    <span>Permanent Address</span>
                                    <span class="transition group-open:rotate-180">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </span>
                                </summary>
                                <div class="mt-4">
                                    <label class="inline-flex items-center mb-4">
                                        <input type="checkbox" name="same_as_current_address" id="same_as_current_address" value="1" {{ old('same_as_current_address', $user->employeeProfile?->same_as_current_address) ? 'checked' : '' }} class="rounded bg-surface-raised border-hairline text-brass focus:ring-brass focus:ring-offset-canvas focus:ring-offset-2">
                                        <span class="ml-2 text-sm text-vellum-muted">Same as Current Address</span>
                                    </label>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="permanent_address1" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Address Line 1</label>
                                            <input type="text" name="permanent_address1" id="permanent_address1" value="{{ old('permanent_address1', $user->employeeProfile?->permanent_address1) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                            @error('permanent_address1') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label for="permanent_address2" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Address Line 2</label>
                                            <input type="text" name="permanent_address2" id="permanent_address2" value="{{ old('permanent_address2', $user->employeeProfile?->permanent_address2) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                            @error('permanent_address2') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label for="permanent_country" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Country</label>
                                            <input type="text" name="permanent_country" id="permanent_country" value="{{ old('permanent_country', $user->employeeProfile?->permanent_country) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                            @error('permanent_country') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label for="permanent_state" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">State</label>
                                            <input type="text" name="permanent_state" id="permanent_state" value="{{ old('permanent_state', $user->employeeProfile?->permanent_state) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                            @error('permanent_state') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label for="permanent_city" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">City</label>
                                            <input type="text" name="permanent_city" id="permanent_city" value="{{ old('permanent_city', $user->employeeProfile?->permanent_city) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                            @error('permanent_city') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label for="permanent_zip" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Zip Code</label>
                                            <input type="text" name="permanent_zip" id="permanent_zip" value="{{ old('permanent_zip', $user->employeeProfile?->permanent_zip) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                            @error('permanent_zip') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                </div>
                            </details>
                        </div>

                        <!-- Collapsible Bank Details -->
                        <div class="mt-4">
                            <details class="group bg-surface rounded-md p-4 border border-hairline">
                                <summary class="font-semibold text-vellum font-display cursor-pointer focus:outline-none flex items-center justify-between select-none text-xs uppercase tracking-wider">
                                    <span>Bank Details</span>
                                    <span class="transition group-open:rotate-180">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </span>
                                </summary>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label for="payment_type" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Payment Type</label>
                                        <input type="text" name="payment_type" id="payment_type" value="{{ old('payment_type', $user->employeeProfile?->payment_type) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('payment_type') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="bank_name" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Bank Name</label>
                                        <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name', $user->employeeProfile?->bank_name) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('bank_name') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="account_holder_name" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Account Holder Name</label>
                                        <input type="text" name="account_holder_name" id="account_holder_name" value="{{ old('account_holder_name', $user->employeeProfile?->account_holder_name) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('account_holder_name') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="account_no" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Account No</label>
                                        <input type="text" name="account_no" id="account_no" value="{{ old('account_no', $user->employeeProfile?->account_no) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('account_no') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="ifsc_code" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">IFSC Code</label>
                                        <input type="text" name="ifsc_code" id="ifsc_code" value="{{ old('ifsc_code', $user->employeeProfile?->ifsc_code) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('ifsc_code') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </details>
                        </div>
                    </div>

                    <!-- 4. EMERGENCY & PREVIOUS WORK CARD -->
                    <div class="bg-surface border border-hairline rounded p-6 shadow-sm">
                        <h3 class="text-sm font-semibold text-brass uppercase tracking-wider mb-4 font-display border-b border-hairline pb-2">Emergency & Previous Work</h3>
                        
                        <div class="space-y-4">
                            <!-- Collapsible Emergency Contact -->
                            <details class="group bg-surface rounded-md p-4 border border-hairline" open>
                                <summary class="font-semibold text-vellum font-display cursor-pointer focus:outline-none flex items-center justify-between select-none text-xs uppercase tracking-wider">
                                    <span>Emergency Contact Details</span>
                                    <span class="transition group-open:rotate-180">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </span>
                                </summary>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label for="emergency_name" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Emergency Contact Name</label>
                                        <input type="text" name="emergency_name" id="emergency_name" value="{{ old('emergency_name', $user->employeeProfile?->emergency_name) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('emergency_name') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="emergency_relationship" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Relationship</label>
                                        <input type="text" name="emergency_relationship" id="emergency_relationship" value="{{ old('emergency_relationship', $user->employeeProfile?->emergency_relationship) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('emergency_relationship') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="md:col-span-2">
                                        <label for="emergency_address" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Address</label>
                                        <input type="text" name="emergency_address" id="emergency_address" value="{{ old('emergency_address', $user->employeeProfile?->emergency_address) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('emergency_address') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="emergency_email" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Email Address</label>
                                        <input type="email" name="emergency_email" id="emergency_email" value="{{ old('emergency_email', $user->employeeProfile?->emergency_email) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('emergency_email') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="emergency_mobile" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Mobile Number</label>
                                        <input type="text" name="emergency_mobile" id="emergency_mobile" value="{{ old('emergency_mobile', $user->employeeProfile?->emergency_mobile) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('emergency_mobile') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </details>

                            <!-- Collapsible Previous Employment -->
                            <details class="group bg-surface rounded-md p-4 border border-hairline">
                                <summary class="font-semibold text-vellum font-display cursor-pointer focus:outline-none flex items-center justify-between select-none text-xs uppercase tracking-wider">
                                    <span>Previous Employment History</span>
                                    <span class="transition group-open:rotate-180">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </span>
                                </summary>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label for="previous_company_name" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Company Name</label>
                                        <input type="text" name="previous_company_name" id="previous_company_name" value="{{ old('previous_company_name', $user->employeeProfile?->previous_company_name) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('previous_company_name') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="previous_job_title" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Job Title</label>
                                        <input type="text" name="previous_job_title" id="previous_job_title" value="{{ old('previous_job_title', $user->employeeProfile?->previous_job_title) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('previous_job_title') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="previous_from_date" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">From Date</label>
                                        <input type="date" name="previous_from_date" id="previous_from_date" value="{{ old('previous_from_date', $user->employeeProfile?->previous_from_date?->format('Y-m-d')) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('previous_from_date') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="previous_to_date" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">To Date</label>
                                        <input type="date" name="previous_to_date" id="previous_to_date" value="{{ old('previous_to_date', $user->employeeProfile?->previous_to_date?->format('Y-m-d')) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('previous_to_date') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </details>
                        </div>
                    </div>

                </div>

                <!-- ================= RIGHT COLUMN ================= -->
                <div class="space-y-6">

                    <!-- 1. SYSTEM & AUTHENTICATION CARD -->
                    <div class="bg-surface border border-hairline rounded p-6 shadow-sm">
                        <h3 class="text-sm font-semibold text-brass uppercase tracking-wider mb-4 font-display border-b border-hairline pb-2">System & Credentials</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Role Selection -->
                            @if(auth()->user()->role === 'admin')
                                <div x-data="{ selectedRole: '{{ old('role', $user->role) }}' }">
                                    <label for="role" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">
                                        System Role
                                    </label>
                                    <select
                                        name="role"
                                        id="role"
                                        x-model="selectedRole"
                                        required
                                        class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5"
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
                                    @error('role')
                                        <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p>
                                    @enderror

                                    @if($user->role === 'manager' && $user->directReports()->exists())
                                        <div x-show="selectedRole !== 'manager'" class="mt-4 p-4 border border-burgundy/30 bg-burgundy-bg/10 rounded-md space-y-4 md:col-span-2">
                                            <p class="text-xs text-burgundy-light font-semibold uppercase tracking-wider">
                                                Attention: Manager has active reports.
                                            </p>
                                            <p class="text-[11px] text-vellum-muted leading-relaxed">
                                                Select a replacement manager or explicitly confirm clearing hierarchy relationships.
                                            </p>
                                            
                                            <div class="space-y-3">
                                                <div>
                                                    <label for="replacement_manager_id" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1">
                                                        Replacement Manager
                                                    </label>
                                                    <select
                                                        name="replacement_manager_id"
                                                        id="replacement_manager_id"
                                                        class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1"
                                                    >
                                                        <option value="">— Choose Replacement —</option>
                                                        @foreach ($managers as $mgr)
                                                            <option value="{{ $mgr->id }}" {{ old('replacement_manager_id') == $mgr->id ? 'selected' : '' }}>
                                                                {{ $mgr->name }} ({{ $mgr->employee_id }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="flex items-center mt-1">
                                                    <input
                                                        type="checkbox"
                                                        name="confirm_clear_hierarchy"
                                                        id="confirm_clear_hierarchy"
                                                        value="1"
                                                        {{ old('confirm_clear_hierarchy') ? 'checked' : '' }}
                                                        class="rounded bg-surface-raised border-hairline text-brass focus:ring-brass focus:ring-offset-canvas focus:ring-offset-2"
                                                    >
                                                    <label for="confirm_clear_hierarchy" class="ml-2 text-[11px] text-vellum-muted font-medium">
                                                        Confirm clearing reporting relationships
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <input type="hidden" name="role" value="{{ $user->role }}">
                            @endif

                            <!-- Status Selection -->
                            <div>
                                <label for="status" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">
                                    Account Status
                                </label>
                                <select
                                    name="status"
                                    id="status"
                                    required
                                    class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5"
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
                                @error('status')
                                    <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Assigned Admin -->
                            <div>
                                <label for="admin_id" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">
                                    Assigned Admin (optional)
                                </label>
                                <select
                                    name="admin_id"
                                    id="admin_id"
                                    class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5"
                                >
                                    <option value="">— No Admin —</option>
                                    @foreach ($admins as $admin)
                                        <option
                                            value="{{ $admin->id }}"
                                            {{ old('admin_id', $user->admin_id) == $admin->id ? 'selected' : '' }}
                                        >
                                            {{ $admin->name }} ({{ $admin->employee_id }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('admin_id')
                                    <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Assigned Manager -->
                            @if(auth()->user()->role === 'admin')
                                <div>
                                    <label for="manager_id" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">
                                        Assigned Manager (optional)
                                    </label>
                                    <select
                                        name="manager_id"
                                        id="manager_id"
                                        class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5"
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
                                    @error('manager_id')
                                        <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            @else
                                <input type="hidden" name="manager_id" value="{{ $user->manager_id }}">
                            @endif

                            <!-- Credentials Update -->
                            <div>
                                <label for="password" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5 font-sans">
                                    New Password
                                </label>
                                <input
                                    type="password"
                                    name="password"
                                    id="password"
                                    placeholder="Leave blank to keep current"
                                    class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5"
                                >
                                @error('password')
                                    <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="password_confirmation" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5 font-sans">
                                    Confirm New Password
                                </label>
                                <input
                                    type="password"
                                    name="password_confirmation"
                                    id="password_confirmation"
                                    placeholder="Leave blank to keep current"
                                    class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- 2. TENURE CARD -->
                    <div class="bg-surface border border-hairline rounded p-6 shadow-sm">
                        <h3 class="text-sm font-semibold text-brass uppercase tracking-wider mb-4 font-display border-b border-hairline pb-2">Tenure Information</h3>
                        
                        <div>
                            <label for="joining_date" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">
                                Joining Date
                            </label>
                            <input
                                type="date"
                                name="joining_date"
                                id="joining_date"
                                value="{{ old('joining_date', $user->joining_date?->format('Y-m-d')) }}"
                                class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5"
                            >
                            @error('joining_date')
                                <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Collapsible Tenure details -->
                        <div class="mt-4">
                            <details class="group bg-surface rounded-md p-4 border border-hairline">
                                <summary class="font-semibold text-vellum font-display cursor-pointer focus:outline-none flex items-center justify-between select-none text-xs uppercase tracking-wider">
                                    <span>Probation, Separation & Experience Details</span>
                                    <span class="transition group-open:rotate-180">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </span>
                                </summary>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label for="probation_period" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Probation Period</label>
                                        <input type="text" name="probation_period" id="probation_period" value="{{ old('probation_period', $user->employeeProfile?->probation_period) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('probation_period') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="probation_confirm_date" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Probation Confirm Date</label>
                                        <input type="date" name="probation_confirm_date" id="probation_confirm_date" value="{{ old('probation_confirm_date', $user->employeeProfile?->probation_confirm_date?->format('Y-m-d')) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('probation_confirm_date') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="separation_date" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Separation Date</label>
                                        <input type="date" name="separation_date" id="separation_date" value="{{ old('separation_date', $user->employeeProfile?->separation_date?->format('Y-m-d')) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('separation_date') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="last_working_day" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Last Working Day (LWD)</label>
                                        <input type="date" name="last_working_day" id="last_working_day" value="{{ old('last_working_day', $user->employeeProfile?->last_working_day?->format('Y-m-d')) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('last_working_day') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="previous_year_experience" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Previous Experience (Years)</label>
                                        <input type="text" name="previous_year_experience" id="previous_year_experience" value="{{ old('previous_year_experience', $user->employeeProfile?->previous_year_experience) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('previous_year_experience') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label for="years_completed" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Years Completed</label>
                                        <input type="text" name="years_completed" id="years_completed" value="{{ old('years_completed', $user->employeeProfile?->years_completed) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('years_completed') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="md:col-span-2">
                                        <label for="overall_year_experience" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Overall Experience (Years)</label>
                                        <input type="text" name="overall_year_experience" id="overall_year_experience" value="{{ old('overall_year_experience', $user->employeeProfile?->overall_year_experience) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        @error('overall_year_experience') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </details>
                        </div>
                    </div>

                    @if(auth()->user()->role === 'admin')
                        <!-- 3. PAYROLL CONFIGURATION CARD -->
                        <div class="bg-surface border border-hairline rounded p-6 shadow-sm">
                            <h3 class="text-sm font-semibold text-brass uppercase tracking-wider mb-4 font-display border-b border-hairline pb-2">Payroll Configuration</h3>
                            
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Payroll Enabled</label>
                                    <select name="payroll_enabled" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        <option value="1" {{ old('payroll_enabled', $user->payrollProfile?->payroll_enabled) ? 'selected' : '' }}>Enabled</option>
                                        <option value="0" {{ !old('payroll_enabled', $user->payrollProfile?->payroll_enabled) ? 'selected' : '' }}>Disabled</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="base_salary" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Base Salary (₹)</label>
                                    <input type="number" step="0.01" name="base_salary" id="base_salary" value="{{ old('base_salary', $user->payrollProfile?->base_salary) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                    @error('base_salary') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="salary_effective_date" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Salary Effective Date</label>
                                    <input type="date" name="salary_effective_date" id="salary_effective_date" value="{{ old('salary_effective_date', $user->payrollProfile?->salary_effective_date?->format('Y-m-d')) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                    @error('salary_effective_date') <p class="text-burgundy-light font-mono text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- 4. LEAVE ADMINISTRATION CARD -->
                        <div class="bg-surface border border-hairline rounded p-6 shadow-sm">
                            <h3 class="text-sm font-semibold text-brass uppercase tracking-wider mb-4 font-display border-b border-hairline pb-2">Leave Administration</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Planned Leave (Days)</label>
                                    <input type="number" step="0.1" name="planned_leave" value="{{ old('planned_leave', $user->leaveBalance?->planned_leave ?? 0.00) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Unplanned Leave (Days)</label>
                                    <input type="number" step="0.1" name="unplanned_leave" value="{{ old('unplanned_leave', $user->leaveBalance?->unplanned_leave ?? 0.00) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Birthday Leave (Days)</label>
                                    @if($birthdayCreditExists)
                                        <input type="number" step="0.1" name="birthday_leave" value="{{ old('birthday_leave', $birthdayBalance) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                        <p class="text-[10px] text-forest mt-1">Active credit found for year {{ $year }}.</p>
                                    @else
                                        <input type="number" step="0.1" name="birthday_leave" value="0.00" disabled class="w-full bg-surface-raised/40 opacity-60 border border-hairline rounded-md text-vellum px-3 py-2 text-sm cursor-not-allowed mt-1.5">
                                        <p class="text-[10px] text-vellum-faint mt-1">Not eligible or credit not yet active for year {{ $year }} (unlocked 1 day before birthday).</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Paternity Leave (Days)</label>
                                    <input type="number" step="0.1" name="paternity_leave" value="{{ old('paternity_leave', $user->leaveBalance?->paternity_leave ?? 0.00) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Maternity Leave (Days)</label>
                                    <input type="number" step="0.1" name="maternity_leave" value="{{ old('maternity_leave', $user->leaveBalance?->maternity_leave ?? 0.00) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Compensatory Leave (Days)</label>
                                    <input type="number" step="0.1" name="compensatory_leave" value="{{ old('compensatory_leave', $user->leaveBalance?->compensatory_leave ?? 0.00) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Carry Forward (Days)</label>
                                    <input type="number" step="0.1" name="carry_forward" value="{{ old('carry_forward', $user->leaveBalance?->carry_forward ?? 0.00) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Pending Leave (Days)</label>
                                    <input type="number" step="0.1" name="pending_leave" value="{{ old('pending_leave', $user->leaveBalance?->pending_leave ?? 0.00) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Utilized Leave (Days)</label>
                                    <input type="number" step="0.1" name="utilized_leave" value="{{ old('utilized_leave', $user->leaveBalance?->utilized_leave ?? 0.00) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider mb-1.5">Remaining Leave (Days)</label>
                                    <input type="number" step="0.1" name="remaining_leave" value="{{ old('remaining_leave', $user->leaveBalance?->remaining_leave ?? 0.00) }}" class="w-full bg-surface-raised border border-hairline rounded-md text-vellum px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none mt-1.5">
                                </div>
                            </div>
                        </div>
                    @endif

                </div>

            </div>

            <!-- Action Buttons at Bottom -->
            <div class="mt-8 flex items-center gap-3 border-t border-hairline pt-6">
                <x-primary-button type="submit">
                    Save Changes
                </x-primary-button>

                <x-secondary-button type="button" onclick="window.location.href='{{ route('employees.index') }}'">
                    Cancel
                </x-secondary-button>
            </div>

        </form>
    </div>

    <!-- Script block for same_as_current_address logic -->
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
                    permField.classList.add('bg-surface-raised/40', 'opacity-65', 'cursor-not-allowed');
                });
            } else {
                permanentFields.forEach(id => {
                    const permField = document.getElementById(id);
                    permField.disabled = false;
                    permField.classList.remove('bg-surface-raised/40', 'opacity-65', 'cursor-not-allowed');
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
</x-app-layout>