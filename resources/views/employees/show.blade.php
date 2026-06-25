<div x-data="{ activeTab: 'personal' }">
    <x-dossier-layout>
        <x-slot name="header">
            <div class="flex items-center justify-between w-full">
                <div>
                    <h1 class="font-display font-medium text-[32px] tracking-wide text-vellum">
                        Employee Dossier
                    </h1>
                    <div class="text-[13px] text-vellum-muted mt-1.5 tracking-wide">
                        Personnel file for {{ $user->name }} · ID: <span class="font-mono text-brass font-semibold">{{ $user->employee_id }}</span>
                    </div>
                </div>
                
                <div class="flex gap-2.5">
                    @if(auth()->user()->role === 'admin')
                        <x-primary-button onclick="window.location.href='{{ route('employees.edit', $user) }}'">
                            Edit Profile
                        </x-primary-button>
                        
                        <form method="POST" action="{{ route('admin.employees.reset-password', $user) }}" onsubmit="return confirm('Are you sure you want to reset this employee\'s password to default?');" class="inline">
                            @csrf
                            <x-danger-button type="submit">
                                Reset Password
                            </x-danger-button>
                        </form>
                    @endif

                    @if(auth()->user()->id === $user->id && auth()->user()->role === 'employee')
                        <button x-data @click="$dispatch('open-modal', 'correction-request-modal')"
                                class="inline-flex items-center px-4 py-2 bg-brass/10 hover:bg-brass/20 text-brass border border-brass/30 rounded font-semibold text-xs uppercase tracking-widest transition duration-150 h-[40px]">
                            Report Incorrect Information
                        </button>
                    @endif
                </div>
            </div>
        </x-slot>

        <!-- Session Notifications -->
        @if(session('success'))
            <div class="rounded bg-forest-bg border border-hairline text-forest px-4 py-3 text-sm mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="rounded bg-burgundy-bg border border-hairline text-burgundy px-4 py-3 text-sm mb-6">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Core Profile Overview Bar -->
        <div class="flex items-center space-x-6 border-b border-hairline pb-6 mb-6">
            <div class="h-16 w-16 rounded-full bg-brass flex items-center justify-center text-canvas text-2xl font-display font-medium shadow-sm">
                {{ substr($user->name, 0, 2) }}
            </div>
            <div>
                <h3 class="text-xl font-bold text-vellum font-display">{{ $user->name }}</h3>
                <p class="text-xs text-vellum-muted mt-0.5">
                    {{ $user->email }} · ID: <span class="font-mono text-brass">{{ $user->employee_id ?? 'N/A' }}</span>
                </p>
                <div class="mt-2 flex gap-2">
                    <span class="tag {{ $user->status === 'active' ? 'present' : 'absent' }} text-[10px] font-mono uppercase tracking-[0.8px] px-2.5 py-0.5 rounded border
                        @if($user->status === 'active') bg-forest-bg text-forest border-transparent
                        @else bg-burgundy-bg text-burgundy border-transparent @endif">
                        {{ $user->status }}
                    </span>
                    <span class="tag text-[10px] font-mono uppercase tracking-[0.8px] px-2.5 py-0.5 rounded border bg-slate-bg text-slate border-transparent">
                        {{ $user->role }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Left Tabs Sidebar -->
        <x-slot name="tabs">
            <button @click="activeTab = 'personal'" :class="activeTab === 'personal' ? 'bg-brass/[0.08] text-brass border-l-[3px] border-brass font-medium' : 'text-vellum-muted hover:bg-brass/[0.03] hover:text-vellum'" class="w-full text-left py-2.5 px-4 rounded-r transition text-[11px] uppercase tracking-wider font-semibold">
                Personal details
            </button>
            <button @click="activeTab = 'employment'" :class="activeTab === 'employment' ? 'bg-brass/[0.08] text-brass border-l-[3px] border-brass font-medium' : 'text-vellum-muted hover:bg-brass/[0.03] hover:text-vellum'" class="w-full text-left py-2.5 px-4 rounded-r transition text-[11px] uppercase tracking-wider font-semibold">
                Employment File
            </button>
            <button @click="activeTab = 'history'" :class="activeTab === 'history' ? 'bg-brass/[0.08] text-brass border-l-[3px] border-brass font-medium' : 'text-vellum-muted hover:bg-brass/[0.03] hover:text-vellum'" class="w-full text-left py-2.5 px-4 rounded-r transition text-[11px] uppercase tracking-wider font-semibold">
                Education & Req
            </button>
        </x-slot>

        <!-- Main Details Pane Switcher -->
        
        <!-- TAB 1: PERSONAL DETAILS -->
        <div x-show="activeTab === 'personal'" class="space-y-8">
            <div>
                <h4 class="text-sm font-semibold text-brass uppercase tracking-wider mb-4">Personal Information</h4>
                <div class="flex flex-col">
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Father's Name</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->father_name ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Mother's Name</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->mother_name ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Gender</span>
                        <span class="text-sm font-medium text-vellum capitalize">{{ $user->employeeProfile?->gender ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Date of Birth</span>
                        <span class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->date_of_birth?->format('M d, Y') ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Marital Status</span>
                        <span class="text-sm font-medium text-vellum capitalize">{{ $user->employeeProfile?->marital_status ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Date of Marriage</span>
                        <span class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->date_of_marriage?->format('M d, Y') ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Nationality</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->nationality ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Blood Group</span>
                        <span class="text-sm font-medium text-vellum font-mono font-semibold">{{ $user->employeeProfile?->blood_group ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Personal Email</span>
                        <span class="text-sm font-medium text-vellum select-all">{{ $user->employeeProfile?->personal_email ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Mobile Number</span>
                        <span class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->mobile_no ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>

            <div>
                <h4 class="text-sm font-semibold text-brass uppercase tracking-wider mb-4">Current Address</h4>
                <div class="flex flex-col">
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Address Line 1</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->current_address1 ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Address Line 2</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->current_address2 ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Country</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->current_country ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">State</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->current_state ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">City</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->current_city ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Zip Code</span>
                        <span class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->current_zip ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>

            <div>
                <h4 class="text-sm font-semibold text-brass uppercase tracking-wider mb-4">Permanent Address</h4>
                <div class="mb-4">
                    <span class="inline-flex items-center px-3 py-1 rounded text-xs font-semibold {{ $user->employeeProfile?->same_as_current_address ? 'bg-forest-bg text-forest border border-forest/20' : 'bg-surface-raised text-vellum-muted border border-hairline' }}">
                        {{ $user->employeeProfile?->same_as_current_address ? 'Same as Current Address' : 'Different Address' }}
                    </span>
                </div>
                
                @if(!$user->employeeProfile?->same_as_current_address)
                    <div class="flex flex-col">
                        <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                            <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Address Line 1</span>
                            <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->permanent_address1 ?? 'N/A' }}</span>
                        </div>
                        <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                            <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Address Line 2</span>
                            <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->permanent_address2 ?? 'N/A' }}</span>
                        </div>
                        <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                            <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Country</span>
                            <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->permanent_country ?? 'N/A' }}</span>
                        </div>
                        <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                            <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">State</span>
                            <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->permanent_state ?? 'N/A' }}</span>
                        </div>
                        <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                            <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">City</span>
                            <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->permanent_city ?? 'N/A' }}</span>
                        </div>
                        <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                            <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Zip Code</span>
                            <span class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->permanent_zip ?? 'N/A' }}</span>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-vellum-muted italic">Same as current address details.</p>
                @endif
            </div>

            <div>
                <h4 class="text-sm font-semibold text-brass uppercase tracking-wider mb-4">Bank Registry</h4>
                <div class="flex flex-col">
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Payment Type</span>
                        <span class="text-sm font-medium text-vellum capitalize">{{ $user->employeeProfile?->payment_type ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Bank Name</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->bank_name ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Account Holder</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->account_holder_name ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Account Number</span>
                        <span class="text-sm font-medium text-vellum font-mono select-all">{{ $user->employeeProfile?->account_no ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">IFSC Code</span>
                        <span class="text-sm font-medium text-brass font-mono uppercase select-all">{{ $user->employeeProfile?->ifsc_code ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>

            <div>
                <h4 class="text-sm font-semibold text-brass uppercase tracking-wider mb-4">Emergency Contact</h4>
                <div class="flex flex-col">
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Contact Name</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->emergency_name ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Relationship</span>
                        <span class="text-sm font-medium text-vellum capitalize">{{ $user->employeeProfile?->emergency_relationship ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Address</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->emergency_address ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Email Address</span>
                        <span class="text-sm font-medium text-vellum select-all">{{ $user->employeeProfile?->emergency_email ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Mobile Phone</span>
                        <span class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->emergency_mobile ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: EMPLOYMENT DOSSIER -->
        <div x-show="activeTab === 'employment'" class="space-y-8" style="display: none;">
            <div>
                <h4 class="text-sm font-semibold text-brass uppercase tracking-wider mb-4">Employment Profile</h4>
                <div class="flex flex-col">
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Payroll Type</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->payroll_type ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Designation</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->designation ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Grade</span>
                        <span class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->grade ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Employee Type</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->employee_type ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Company</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->company ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Office Location</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->location ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Biometric ID</span>
                        <span class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->biometric_id ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Shift Schedule</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->shift ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Leave Rule</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->leave_rule ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Office Landline</span>
                        <span class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->office_landline ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Notice Days</span>
                        <span class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->notice_days ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Joining Date</span>
                        <span class="text-sm font-medium text-vellum font-mono">{{ $user->joining_date?->format('M d, Y') ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Contract End Date</span>
                        <span class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->contract_end_date?->format('M d, Y') ?? 'N/A' }}</span>
                    </div>
                    @if($user->role !== 'admin')
                        <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                            <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Leave Balance</span>
                            <span class="text-sm font-semibold text-brass font-mono">{{ number_format($user->leave_balance, 2) }} days</span>
                        </div>
                    @endif
                </div>
            </div>

            <div>
                <h4 class="text-sm font-semibold text-brass uppercase tracking-wider mb-4">Government Registry</h4>
                <div class="flex flex-col">
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">PF UAN</span>
                        <span class="text-sm font-medium text-vellum font-mono select-all">{{ $user->employeeProfile?->pf_uan ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Passport Number</span>
                        <span class="text-sm font-medium text-vellum font-mono select-all">{{ $user->employeeProfile?->passport_no ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Aadhar Card</span>
                        <span class="text-sm font-medium text-vellum font-mono select-all">{{ $user->employeeProfile?->aadhar_card ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">PAN Card</span>
                        <span class="text-sm font-medium text-brass font-mono uppercase select-all">{{ $user->employeeProfile?->pan ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">PF Number</span>
                        <span class="text-sm font-medium text-vellum font-mono select-all">{{ $user->employeeProfile?->pf_no ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">ESI Number</span>
                        <span class="text-sm font-medium text-vellum font-mono select-all">{{ $user->employeeProfile?->esi_number ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Date of Gratuity</span>
                        <span class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->date_of_gratuity?->format('M d, Y') ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>

            <div>
                <h4 class="text-sm font-semibold text-brass uppercase tracking-wider mb-4">Tenure & Performance</h4>
                <div class="flex flex-col">
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Probation Period</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->probation_period ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Probation Confirm Date</span>
                        <span class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->probation_confirm_date?->format('M d, Y') ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Separation Date</span>
                        <span class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->separation_date?->format('M d, Y') ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Last Working Day</span>
                        <span class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->last_working_day?->format('M d, Y') ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Prior Experience</span>
                        <span class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->previous_year_experience ?? 'N/A' }} years</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Years Completed</span>
                        <span class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->years_completed ?? 'N/A' }} years</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Overall Experience</span>
                        <span class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->overall_year_experience ?? 'N/A' }} years</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Hiring Source</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->hiring_source ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Source of Verification</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->source_of_verification ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: HISTORY & REQUESTS -->
        <div x-show="activeTab === 'history'" class="space-y-8" style="display: none;">
            <div>
                <h4 class="text-sm font-semibold text-brass uppercase tracking-wider mb-4">Academic Credentials</h4>
                <div class="flex flex-col">
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Degree Name</span>
                        <span class="text-sm font-semibold text-vellum">{{ $user->employeeProfile?->degree_name ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Institution Name</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->institution_name ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Passing Year</span>
                        <span class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->passing_year ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Score Percentage</span>
                        <span class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->percentage ?? 'N/A' }}%</span>
                    </div>
                </div>
            </div>

            <div>
                <h4 class="text-sm font-semibold text-brass uppercase tracking-wider mb-4">Previous Employment</h4>
                <div class="flex flex-col">
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Previous Company</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->previous_company_name ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Job Title</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->previous_job_title ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">From Date</span>
                        <span class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->previous_from_date?->format('M d, Y') ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">To Date</span>
                        <span class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->previous_to_date?->format('M d, Y') ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>

            <!-- Profile Correction Requests Section -->
            @if(auth()->user()->id === $user->id || auth()->user()->role === 'admin')
                <div>
                    <h4 class="text-sm font-semibold text-brass uppercase tracking-wider mb-4">Profile Correction Requests</h4>

                    @php
                        $correctionRequests = \App\Models\ProfileCorrectionRequest::where('user_id', $user->id)->latest()->get();
                    @endphp

                    @if($correctionRequests->isEmpty())
                        <p class="text-sm text-vellum-faint italic">No correction requests submitted yet.</p>
                    @else
                        <div class="space-y-4">
                            @foreach($correctionRequests as $req)
                                <div class="p-4 rounded border {{ $req->status === 'pending' ? 'bg-cognac-bg border-cognac/30 text-vellum' : 'bg-forest-bg border-forest/30 text-vellum' }}">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-xs font-semibold text-vellum-muted">
                                            Submitted on {{ $req->created_at->timezone('Asia/Kolkata')->format('Y-m-d h:i A') }}
                                        </span>
                                        <div class="flex items-center gap-2">
                                            <span class="px-2.5 py-0.5 rounded bg-brass/15 border border-brass/25 text-brass text-[11px] font-mono font-bold">{{ $req->field }}</span>
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
        </div>
    </x-dossier-layout>

    <!-- Correction Request Modal -->
    <x-modal name="correction-request-modal" :show="$errors->any()">
        <form method="POST" action="{{ route('employee.corrections.store') }}" class="p-6">
            @csrf
            <h2 class="font-display font-medium text-lg text-vellum mb-4">
                Report Incorrect Profile Information
            </h2>
            
            <!-- Field Dropdown -->
            <div class="mb-4">
                <x-input-label for="field" value="Field to Correct" />
                <select name="field" id="field" required class="w-full bg-surface-raised border border-hairline text-vellum rounded px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                    <option value="">Select a field...</option>
                    <option value="Phone Number">Phone Number</option>
                    <option value="Personal Email">Personal Email</option>
                    <option value="Official Email">Official Email</option>
                    <option value="Department">Department</option>
                    <option value="Designation">Designation</option>
                    <option value="Reporting Manager">Reporting Manager</option>
                    <option value="Joining Date">Joining Date</option>
                    <option value="Address">Address</option>
                    <option value="Bank Details">Bank Details</option>
                    <option value="Emergency Contact">Emergency Contact</option>
                    <option value="Other">Other</option>
                </select>
                <x-input-error :messages="$errors->get('field')" class="mt-2" />
            </div>

            <!-- Message Details -->
            <div class="mb-6">
                <x-input-label for="message" value="Correction Details (Be specific)" />
                <textarea name="message" id="message" rows="4" required minlength="5" maxlength="1000"
                          placeholder="Please specify what needs correction and supply the correct values..."
                          class="w-full bg-surface-raised border border-hairline text-vellum rounded px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none"></textarea>
                <x-input-error :messages="$errors->get('message')" class="mt-2" />
            </div>

            <div class="flex justify-end gap-2.5">
                <x-secondary-button x-on:click="$dispatch('close-modal', 'correction-request-modal')">
                    Cancel
                </x-secondary-button>
                <x-primary-button type="submit">
                    Submit Request
                </x-primary-button>
            </div>
        </form>
    </x-modal>
</div>
