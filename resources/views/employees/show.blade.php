<x-app-layout>
    <div x-data="{ openCorrectionModal: false }" @open-profile-correction-modal.window="openCorrectionModal = true">
        <x-slot name="header">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-vellum leading-tight font-display">
                    {{ __('Employee Profile — ') }} {{ $user->name }}
                </h2>
                
                <div class="flex gap-2">
                    @if(auth()->user()->role === 'admin')
                        <a href="{{ route('employees.edit', $user) }}"
                           class="inline-flex items-center px-4 py-2 bg-brass hover:bg-brass/90 text-canvas font-bold uppercase tracking-widest rounded-md focus:outline-none focus:ring-2 focus:ring-brass/30 focus:ring-offset-2 focus:ring-offset-canvas transition duration-150 text-xs">
                            Edit Profile
                        </a>
                        
                        <form method="POST" action="{{ route('admin.employees.reset-password', $user) }}" onsubmit="return confirm('Are you sure you want to reset this employee\'s password to default?');" class="inline">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-burgundy hover:bg-burgundy/90 text-vellum border border-burgundy/30 rounded-md font-semibold text-xs uppercase tracking-widest transition duration-150">
                                Reset Password
                            </button>
                        </form>
                    @endif

                    @if(auth()->user()->id === $user->id && auth()->user()->role === 'employee')
                        <button x-data @click="$dispatch('open-profile-correction-modal')"
                                class="inline-flex items-center px-4 py-2 bg-brass/10 hover:bg-brass/20 text-brass border border-brass/30 rounded-md font-semibold text-xs uppercase tracking-widest transition duration-150">
                            Report Incorrect Information
                        </button>
                    @endif
                </div>
            </div>
        </x-slot>

    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="rounded-md bg-forest-bg border border-forest/30 text-forest px-4 py-3 shadow-sm text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-md bg-burgundy-bg border border-burgundy/30 text-burgundy px-4 py-3 shadow-sm text-sm">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            <!-- Core Information Summary Card (Glassmorphic) -->
            <div class="glass-panel overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-hairline bg-surface/30">
                    <div class="flex items-center space-x-4">
                        <div class="h-16 w-16 rounded-full bg-brass flex items-center justify-center text-canvas text-2xl font-bold">
                            {{ substr($user->name, 0, 2) }}
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-vellum font-display">{{ $user->name }}</h3>
                            <p class="text-sm text-vellum-muted">{{ $user->email }} | {{ $user->employee_id ?? 'No Employee ID' }}</p>
                            <span class="inline-flex items-center mt-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold capitalize {{ $user->status === 'active' ? 'bg-forest-bg text-forest border border-forest/30' : 'bg-burgundy-bg text-burgundy border border-burgundy/30' }}">
                                {{ $user->status }}
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <span class="block text-xs font-semibold text-vellum-faint uppercase tracking-wider">Role</span>
                        <span class="text-sm font-medium text-vellum capitalize">{{ $user->role }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold text-vellum-faint uppercase tracking-wider">Department</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->department?->name ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold text-vellum-faint uppercase tracking-wider">Phone</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->phone ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold text-vellum-faint uppercase tracking-wider">Joining Date</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->joining_date?->format('Y-m-d') ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold text-vellum-faint uppercase tracking-wider">Reporting Manager</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->manager?->name ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="block text-xs font-semibold text-vellum-faint uppercase tracking-wider">Assigned Admin</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->admin?->name ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>

            <!-- Profile Sections -->
            <div class="panel space-y-8">
                
                <!-- 1. Personal -->
                <div class="border-b border-hairline pb-6">
                    <h4 class="text-lg font-semibold text-brass mb-4 flex items-center font-display">
                        <span class="bg-brass/10 p-1.5 rounded-md mr-2 text-brass">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        </span>
                        Personal Details
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><span class="text-xs text-vellum-faint">Father's Name</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->father_name ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Mother's Name</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->mother_name ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Gender</span><p class="text-sm font-medium text-vellum capitalize">{{ $user->employeeProfile?->gender ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Date of Birth</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->date_of_birth?->format('Y-m-d') ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Marital Status</span><p class="text-sm font-medium text-vellum capitalize">{{ $user->employeeProfile?->marital_status ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Date of Marriage</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->date_of_marriage?->format('Y-m-d') ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Nationality</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->nationality ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Blood Group</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->blood_group ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Personal Email</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->personal_email ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Mobile No</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->mobile_no ?? 'N/A' }}</p></div>
                    </div>
                </div>

                <!-- 2. Government IDs -->
                <div class="border-b border-hairline pb-6">
                    <h4 class="text-lg font-semibold text-brass mb-4 flex items-center font-display">
                        <span class="bg-brass/10 p-1.5 rounded-md mr-2 text-brass">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.333 0 4 .667 4 2V17H6v-1c0-1.333 2.667-2 4-2z" /></svg>
                        </span>
                        Government IDs
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><span class="text-xs text-vellum-faint">PF UAN</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->pf_uan ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Passport No</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->passport_no ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Aadhar Card</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->aadhar_card ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">PAN</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->pan ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">PF No</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->pf_no ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">ESI Number</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->esi_number ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Date of Gratuity</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->date_of_gratuity?->format('Y-m-d') ?? 'N/A' }}</p></div>
                    </div>
                </div>

                <!-- 3. Employment -->
                <div class="border-b border-hairline pb-6">
                    <h4 class="text-lg font-semibold text-brass mb-4 flex items-center font-display">
                        <span class="bg-brass/10 p-1.5 rounded-md mr-2 text-brass">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                        </span>
                        Employment Details
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><span class="text-xs text-vellum-faint">Payroll Type</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->payroll_type ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Contract End Date</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->contract_end_date?->format('Y-m-d') ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Office Landline</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->office_landline ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Leave Rule</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->leave_rule ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Shift</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->shift ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Designation</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->designation ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Grade</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->grade ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Employee Type</span><p class="text-sm font-medium text-vellum font-medium">{{ $user->employeeProfile?->employee_type ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Company</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->company ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Location</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->location ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Biometric ID</span><p class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->biometric_id ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Hiring Source</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->hiring_source ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Source of Verification</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->source_of_verification ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">City Type</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->city_type ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Notice Days</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->notice_days ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">State Name</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->state_name ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Joining Date</span><p class="text-sm font-medium text-vellum">{{ $user->joining_date?->format('Y-m-d') ?? 'N/A' }}</p></div>
                    </div>
                </div>

                <!-- 4. Current Address -->
                <div class="border-b border-hairline pb-6">
                    <h4 class="text-lg font-semibold text-brass mb-4 flex items-center font-display">
                        <span class="bg-brass/10 p-1.5 rounded-md mr-2 text-brass">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                        </span>
                        Current Address
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><span class="text-xs text-vellum-faint">Address Line 1</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->current_address1 ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Address Line 2</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->current_address2 ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Country</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->current_country ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">State</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->current_state ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">City</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->current_city ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Zip Code</span><p class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->current_zip ?? 'N/A' }}</p></div>
                    </div>
                </div>

                <!-- 5. Permanent Address -->
                <div class="border-b border-hairline pb-6">
                    <h4 class="text-lg font-semibold text-brass mb-4 flex items-center font-display">
                        <span class="bg-brass/10 p-1.5 rounded-md mr-2 text-brass">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        </span>
                        Permanent Address
                    </h4>
                    <div class="mb-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $user->employeeProfile?->same_as_current_address ? 'bg-forest-bg text-forest border border-forest/20' : 'bg-surface-raised text-vellum-muted border border-hairline' }}">
                            {{ $user->employeeProfile?->same_as_current_address ? 'Same as Current Address' : 'Different Address' }}
                        </span>
                    </div>
                    
                    @if(!$user->employeeProfile?->same_as_current_address)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><span class="text-xs text-vellum-faint">Address Line 1</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->permanent_address1 ?? 'N/A' }}</p></div>
                            <div><span class="text-xs text-vellum-faint">Address Line 2</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->permanent_address2 ?? 'N/A' }}</p></div>
                            <div><span class="text-xs text-vellum-faint">Country</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->permanent_country ?? 'N/A' }}</p></div>
                            <div><span class="text-xs text-vellum-faint">State</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->permanent_state ?? 'N/A' }}</p></div>
                            <div><span class="text-xs text-vellum-faint">City</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->permanent_city ?? 'N/A' }}</p></div>
                            <div><span class="text-xs text-vellum-faint">Zip Code</span><p class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->permanent_zip ?? 'N/A' }}</p></div>
                        </div>
                    @else
                        <p class="text-sm text-vellum-muted italic">Same as current address details.</p>
                    @endif
                </div>

                <!-- 6. Bank Details -->
                <div class="border-b border-hairline pb-6">
                    <h4 class="text-lg font-semibold text-brass mb-4 flex items-center font-display">
                        <span class="bg-brass/10 p-1.5 rounded-md mr-2 text-brass">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </span>
                        Bank Details
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><span class="text-xs text-vellum-faint">Payment Type</span><p class="text-sm font-medium text-vellum capitalize">{{ $user->employeeProfile?->payment_type ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Bank Name</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->bank_name ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Account Holder Name</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->account_holder_name ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Account No</span><p class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->account_no ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">IFSC Code</span><p class="text-sm font-medium text-vellum font-mono uppercase">{{ $user->employeeProfile?->ifsc_code ?? 'N/A' }}</p></div>
                    </div>
                </div>

                <!-- 7. Emergency Contact -->
                <div class="border-b border-hairline pb-6">
                    <h4 class="text-lg font-semibold text-brass mb-4 flex items-center font-display">
                        <span class="bg-brass/10 p-1.5 rounded-md mr-2 text-brass">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                        </span>
                        Emergency Contact
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><span class="text-xs text-vellum-faint">Emergency Contact Name</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->emergency_name ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Relationship</span><p class="text-sm font-medium text-vellum capitalize">{{ $user->employeeProfile?->emergency_relationship ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Address</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->emergency_address ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Email Address</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->emergency_email ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Mobile Phone</span><p class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->emergency_mobile ?? 'N/A' }}</p></div>
                    </div>
                </div>

                <!-- 8. Education -->
                <div class="border-b border-hairline pb-6">
                    <h4 class="text-lg font-semibold text-brass mb-4 flex items-center font-display">
                        <span class="bg-brass/10 p-1.5 rounded-md mr-2 text-brass">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" /></svg>
                        </span>
                        Education Details
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><span class="text-xs text-vellum-faint">Degree Name</span><p class="text-sm font-medium text-vellum font-semibold">{{ $user->employeeProfile?->degree_name ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Institution Name</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->institution_name ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Passing Year</span><p class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->passing_year ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Percentage</span><p class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->percentage ?? 'N/A' }}%</p></div>
                    </div>
                </div>

                <!-- 9. Previous Employment -->
                <div class="border-b border-hairline pb-6">
                    <h4 class="text-lg font-semibold text-brass mb-4 flex items-center font-display">
                        <span class="bg-brass/10 p-1.5 rounded-md mr-2 text-brass">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" /></svg>
                        </span>
                        Previous Employment
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><span class="text-xs text-vellum-faint">Previous Company Name</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->previous_company_name ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Job Title</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->previous_job_title ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">From Date</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->previous_from_date?->format('Y-m-d') ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">To Date</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->previous_to_date?->format('Y-m-d') ?? 'N/A' }}</p></div>
                    </div>
                </div>

                <!-- 10. Tenure -->
                <div>
                    <h4 class="text-lg font-semibold text-brass mb-4 flex items-center font-display">
                        <span class="bg-brass/10 p-1.5 rounded-md mr-2 text-brass">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        </span>
                        Tenure Details
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div><span class="text-xs text-vellum-faint">Probation Period</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->probation_period ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Probation Confirm Date</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->probation_confirm_date?->format('Y-m-d') ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Separation Date</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->separation_date?->format('Y-m-d') ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Last Working Day</span><p class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->last_working_day?->format('Y-m-d') ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Previous Experience (Years)</span><p class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->previous_year_experience ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Years Completed</span><p class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->years_completed ?? 'N/A' }}</p></div>
                        <div><span class="text-xs text-vellum-faint">Overall Experience (Years)</span><p class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->overall_year_experience ?? 'N/A' }}</p></div>
                    </div>
                </div>

            </div>

            <!-- Profile Correction Requests Section -->
            @if(auth()->user()->id === $user->id || auth()->user()->role === 'admin')
                <div class="panel mt-6 space-y-4">
                    <h4 class="text-lg font-semibold text-brass mb-2 flex items-center font-display">
                        <span class="bg-brass/10 p-1.5 rounded-md mr-2 text-brass">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        </span>
                        Profile Correction Requests
                    </h4>

                    @php
                        $correctionRequests = \App\Models\ProfileCorrectionRequest::where('user_id', $user->id)->latest()->get();
                    @endphp

                    @if($correctionRequests->isEmpty())
                        <p class="text-sm text-vellum-faint">No correction requests submitted yet.</p>
                    @else
                        <div class="space-y-4">
                            @foreach($correctionRequests as $req)
                                <div class="p-4 rounded-lg border {{ $req->status === 'pending' ? 'bg-cognac-bg border-cognac/30 text-vellum' : 'bg-forest-bg border-forest/30 text-vellum' }}">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-semibold text-vellum-muted">
                                            Submitted on {{ $req->created_at->format('Y-m-d h:i A') }}
                                        </span>
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-0.5 rounded bg-brass/10 border border-brass/25 text-brass text-[11px] font-bold">{{ $req->field }}</span>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize {{ $req->status === 'pending' ? 'bg-cognac-bg text-cognac border border-cognac/20' : 'bg-forest-bg text-forest border border-forest/20' }}">
                                                {{ $req->status }}
                                            </span>
                                        </div>
                                    </div>
                                    <p class="text-sm text-vellum whitespace-pre-line font-medium">{{ $req->message }}</p>
                                    
                                    @if($req->status === 'resolved')
                                        <div class="mt-2 pt-2 border-t border-dashed border-hairline">
                                            <span class="block text-xs font-semibold text-vellum-faint">Admin Note:</span>
                                            <p class="text-sm text-vellum whitespace-pre-line italic">{{ $req->admin_note ?? 'None' }}</p>
                                            <span class="block text-[10px] text-vellum-faint mt-1">
                                                Resolved by {{ $req->resolver?->name ?? 'Admin' }} on {{ $req->resolved_at?->format('Y-m-d h:i A') }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            <!-- Correction Request Modal -->
            <div x-show="openCorrectionModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" x-transition>
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <!-- Background overlay -->
                    <div class="fixed inset-0 transition-opacity bg-black/70" @click="openCorrectionModal = false"></div>

                    <!-- Modal panel -->
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom glass-panel text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-hairline">
                        <form method="POST" action="{{ route('employee.corrections.store') }}">
                            @csrf
                            <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <div class="sm:flex sm:items-start">
                                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                        <h3 class="text-lg leading-6 font-semibold text-brass mb-4 font-display" id="modal-title">
                                            Report Incorrect Profile Information
                                        </h3>
                                        
                                        <!-- Field Dropdown -->
                                        <div class="mb-4 text-start">
                                            <label for="field" class="block text-sm font-medium text-vellum-muted mb-1">Field to Correct</label>
                                            <select name="field" id="field" required class="w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1">
                                                <option value="" class="bg-surface">Select a field...</option>
                                                <option value="Phone Number" class="bg-surface">Phone Number</option>
                                                <option value="Personal Email" class="bg-surface">Personal Email</option>
                                                <option value="Official Email" class="bg-surface">Official Email</option>
                                                <option value="Department" class="bg-surface">Department</option>
                                                <option value="Designation" class="bg-surface">Designation</option>
                                                <option value="Reporting Manager" class="bg-surface">Reporting Manager</option>
                                                <option value="Joining Date" class="bg-surface">Joining Date</option>
                                                <option value="Address" class="bg-surface">Address</option>
                                                <option value="Bank Details" class="bg-surface">Bank Details</option>
                                                <option value="Emergency Contact" class="bg-surface">Emergency Contact</option>
                                                <option value="Other" class="bg-surface">Other</option>
                                            </select>
                                        </div>

                                        <!-- Message Details -->
                                        <div class="mb-4 text-start">
                                            <label for="message" class="block text-sm font-medium text-vellum-muted mb-1">Correction Details (Be specific)</label>
                                            <textarea name="message" id="message" rows="4" required minlength="5" maxlength="1000"
                                                      placeholder="Please specify what needs correction and supply the correct values..."
                                                      class="w-full bg-surface-raised border border-hairline text-vellum rounded-md shadow-sm focus:border-brass/50 focus:ring focus:ring-brass/30 focus:ring-1"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2 bg-surface/30 border-t border-hairline -mx-6 -mb-6 mt-4">
                                <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-brass hover:bg-brass/90 text-base font-bold text-canvas sm:ml-3 sm:w-auto sm:text-sm uppercase">
                                    Submit Request
                                </button>
                                <button type="button" @click="openCorrectionModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-hairline shadow-sm px-4 py-2 bg-surface-raised text-base font-semibold text-vellum hover:bg-surface-raised/80 sm:mt-0 sm:w-auto sm:text-sm">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
</x-app-layout>
