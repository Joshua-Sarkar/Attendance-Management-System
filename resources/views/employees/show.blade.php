@php
    $year = now()->year;
    $birthdayCredit = \App\Models\LeaveCredit::where('user_id', $user->id)
        ->where('source_identifier', "birthday_{$year}")
        ->first();
    $birthdayCreditExists = $birthdayCredit !== null;
    $birthdayBalance = $birthdayCreditExists ? (float) ($birthdayCredit->amount - $birthdayCredit->used_amount) : 0.00;
@endphp

<x-dossier-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1.5">
            <h1 class="font-display font-medium text-[32px] tracking-wide text-vellum">
                Employee Dossier
            </h1>
            <div class="text-[13px] text-vellum-muted tracking-wide">
                Personnel file for {{ $user->name }} · ID: <span class="font-mono text-brass font-semibold">{{ $user->employee_id }}</span>
            </div>
        </div>
    </x-slot>

    <!-- Sticky Sidebar Navigation Links -->
    <x-slot name="tabs">
        <nav class="flex flex-col gap-1 w-full">
            <a href="#identity" @click="activeSection = 'identity'" :class="activeSection === 'identity' ? 'bg-brass/[0.08] text-brass border-l-[3px] border-brass font-medium' : 'text-vellum-muted hover:bg-brass/[0.03] hover:text-vellum'" class="w-full text-left py-2.5 px-4 rounded-r transition text-[11px] uppercase tracking-wider font-semibold block">
                Identity
            </a>
            <a href="#employment" @click="activeSection = 'employment'" :class="activeSection === 'employment' ? 'bg-brass/[0.08] text-brass border-l-[3px] border-brass font-medium' : 'text-vellum-muted hover:bg-brass/[0.03] hover:text-vellum'" class="w-full text-left py-2.5 px-4 rounded-r transition text-[11px] uppercase tracking-wider font-semibold block">
                Employment
            </a>
            <a href="#contact" @click="activeSection = 'contact'" :class="activeSection === 'contact' ? 'bg-brass/[0.08] text-brass border-l-[3px] border-brass font-medium' : 'text-vellum-muted hover:bg-brass/[0.03] hover:text-vellum'" class="w-full text-left py-2.5 px-4 rounded-r transition text-[11px] uppercase tracking-wider font-semibold block">
                Contact
            </a>
            <a href="#emergency" @click="activeSection = 'emergency'" :class="activeSection === 'emergency' ? 'bg-brass/[0.08] text-brass border-l-[3px] border-brass font-medium' : 'text-vellum-muted hover:bg-brass/[0.03] hover:text-vellum'" class="w-full text-left py-2.5 px-4 rounded-r transition text-[11px] uppercase tracking-wider font-semibold block">
                Emergency
            </a>
            <a href="#payroll" @click="activeSection = 'payroll'" :class="activeSection === 'payroll' ? 'bg-brass/[0.08] text-brass border-l-[3px] border-brass font-medium' : 'text-vellum-muted hover:bg-brass/[0.03] hover:text-vellum'" class="w-full text-left py-2.5 px-4 rounded-r transition text-[11px] uppercase tracking-wider font-semibold block">
                Payroll
            </a>
            <a href="#timeline" @click="activeSection = 'timeline'" :class="activeSection === 'timeline' ? 'bg-brass/[0.08] text-brass border-l-[3px] border-brass font-medium' : 'text-vellum-muted hover:bg-brass/[0.03] hover:text-vellum'" class="w-full text-left py-2.5 px-4 rounded-r transition text-[11px] uppercase tracking-wider font-semibold block">
                Timeline
            </a>
            <a href="#history" @click="activeSection = 'history'" :class="activeSection === 'history' ? 'bg-brass/[0.08] text-brass border-l-[3px] border-brass font-medium' : 'text-vellum-muted hover:bg-brass/[0.03] hover:text-vellum'" class="w-full text-left py-2.5 px-4 rounded-r transition text-[11px] uppercase tracking-wider font-semibold block">
                History
            </a>
            @if(auth()->user()->id === $user->id || auth()->user()->role === 'admin')
                <a href="#corrections" @click="activeSection = 'corrections'" :class="activeSection === 'corrections' ? 'bg-brass/[0.08] text-brass border-l-[3px] border-brass font-medium' : 'text-vellum-muted hover:bg-brass/[0.03] hover:text-vellum'" class="w-full text-left py-2.5 px-4 rounded-r transition text-[11px] uppercase tracking-wider font-semibold block">
                    Corrections
                </a>
            @endif
        </nav>
    </x-slot>

    <x-slot name="summary">
        <!-- RIGHT COLUMN: EMPLOYEE SUMMARY CARD -->
        <div class="w-full bg-surface border border-hairline rounded p-6 flex flex-col items-center text-center shadow-sm">
            <!-- Avatar box -->
            @if($user->profile_photo_path)
                <img src="{{ asset('storage/' . $user->profile_photo_path) }}" class="h-20 w-20 rounded object-cover border border-brass mb-4 shadow-sm" alt="{{ $user->name }}">
            @else
                <div class="h-20 w-20 rounded bg-brass flex items-center justify-center text-canvas text-3xl font-display font-medium border border-brass mb-4 shadow-sm">
                    {{ substr($user->name, 0, 2) }}
                </div>
            @endif
            
            <!-- Name -->
            <h3 class="text-lg font-bold text-vellum font-display leading-tight">{{ $user->name }}</h3>
            <p class="text-xs text-brass font-semibold font-mono mt-1.5">{{ $user->employee_id ?? 'N/A' }}</p>
            <p class="text-xs text-vellum-muted mt-1 truncate w-full" title="{{ $user->email }}">{{ $user->email }}</p>
            
            <!-- Status & Role Tags -->
            <div class="mt-4 flex flex-wrap gap-2 justify-center w-full">
                <span class="tag {{ $user->status === 'active' ? 'present' : 'absent' }} text-[9.5px] font-mono uppercase tracking-[0.8px] px-2.5 py-0.5 rounded border
                    @if($user->status === 'active') bg-forest-bg text-forest border-transparent
                    @else bg-burgundy-bg text-burgundy border-transparent @endif">
                    {{ $user->status }}
                </span>
                <span class="tag text-[9.5px] font-mono uppercase tracking-[0.8px] px-2.5 py-0.5 rounded border bg-slate-bg text-slate border-transparent">
                    {{ $user->role }}
                </span>
            </div>
            
            <!-- Profile Overview Details -->
            <div class="w-full mt-6 space-y-2.5 border-t border-hairline pt-5 text-left text-xs">
                <div class="flex justify-between items-center">
                    <span class="font-semibold text-vellum-faint">Department:</span>
                    <span class="font-medium text-vellum truncate max-w-[120px]" title="{{ $user->department?->name ?? 'None' }}">{{ $user->department?->name ?? 'None' }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="font-semibold text-vellum-faint">Manager:</span>
                    <span class="font-medium text-vellum truncate max-w-[120px]" title="{{ $user->manager?->name ?? 'None' }}">{{ $user->manager?->name ?? 'None' }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="font-semibold text-vellum-faint">Designation:</span>
                    <span class="font-medium text-vellum truncate max-w-[120px]" title="{{ $user->employeeProfile?->designation ?? 'None' }}">{{ $user->employeeProfile?->designation ?? 'None' }}</span>
                </div>
            </div>

            <!-- Admin Actions inside the card -->
            <div class="w-full border-t border-hairline mt-5 pt-4 flex flex-col gap-2">
                @if(auth()->user()->role === 'admin')
                    <x-primary-button onclick="window.location.href='{{ route('employees.edit', $user) }}'" class="w-full justify-center !h-[36px] text-xs">
                        Edit Profile
                    </x-primary-button>
                    
                    <form method="POST" action="{{ route('admin.employees.reset-password', $user) }}" onsubmit="return confirm('Are you sure you want to reset this employee\'s password to default?');" class="w-full">
                        @csrf
                        <x-danger-button type="submit" class="w-full justify-center !h-[36px] text-xs">
                            Reset Password
                        </x-danger-button>
                    </form>

                    @if(auth()->user()->id !== $user->id)
                        <form method="POST" action="{{ route('employees.destroy', $user) }}" onsubmit="return confirm('WARNING: This action is permanent. Deleting this employee will permanently remove their employee record, payroll profile, leave balances, salary history, external identifiers, import metadata, attendance records, leave requests, and all related audit trails. This action CANNOT be undone. Are you sure you want to permanently delete this employee?');" class="w-full">
                            @csrf
                            @method('DELETE')
                            <x-danger-button type="submit" class="w-full justify-center !h-[36px] text-xs bg-red-700 hover:bg-red-800 text-white font-bold border border-red-700 rounded">
                                Delete Employee
                            </x-danger-button>
                        </form>
                    @endif
                @endif

                @if(auth()->user()->id === $user->id && auth()->user()->role === 'employee')
                    <button x-data @click="$dispatch('open-modal', 'correction-request-modal')"
                            class="w-full inline-flex items-center justify-center px-4 py-2 bg-brass/10 hover:bg-brass/20 text-brass border border-brass/30 rounded font-semibold text-xs uppercase tracking-widest transition duration-150 h-[36px]">
                        Report Incorrect Info
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

    <!-- Main Ledger Dossier Body -->
    <div class="space-y-12">

            <!-- IDENTITY BLOCK -->
            <div id="identity" x-show="activeSection === 'identity'" class="scroll-mt-6 flex flex-col md:flex-row items-start md:items-center justify-between border-b border-hairline pb-8">
                <div class="flex items-center space-x-6">
                    @if($user->profile_photo_path)
                        <img src="{{ asset('storage/' . $user->profile_photo_path) }}" class="h-20 w-20 rounded object-cover shadow-sm border border-brass" alt="{{ $user->name }}">
                    @else
                        <div class="h-20 w-20 rounded bg-brass flex items-center justify-center text-canvas text-3xl font-display font-medium shadow-sm border border-brass">
                            {{ substr($user->name, 0, 2) }}
                        </div>
                    @endif
                    <div>
                        <h3 class="text-2xl font-bold text-vellum font-display font-medium">{{ $user->name }}</h3>
                        <p class="text-sm text-vellum-muted mt-1">
                            {{ $user->email }} · ID: <span class="font-mono text-brass font-semibold">{{ $user->employee_id ?? 'N/A' }}</span>
                        </p>
                        <div class="mt-3 flex gap-2">
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
                <div class="mt-4 md:mt-0 text-left md:text-right text-xs text-vellum-faint space-y-1 font-mono">
                    <div>Created: {{ $user->created_at->format('Y-m-d H:i') }}</div>
                    <div>Last Updated: {{ $user->updated_at->format('Y-m-d H:i') }}</div>
                </div>
            </div>

            <!-- PERSONNEL INFORMATION -->
            <div id="personnel" x-show="activeSection === 'identity'" class="scroll-mt-6 border-b border-hairline pb-8">
                <h4 class="text-sm font-semibold text-brass uppercase tracking-wider mb-4">Personnel Information</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-1">
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

            <!-- EMPLOYMENT -->
            <div id="employment" x-show="activeSection === 'employment'" class="scroll-mt-6 border-b border-hairline pb-8">
                <h4 class="text-sm font-semibold text-brass uppercase tracking-wider mb-4">Employment Profile</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-1">
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
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Employee Category</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->employee_category ?? 'N/A' }}</span>
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
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Department</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->department?->name ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Assigned Manager</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->manager?->name ?? 'None' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Assigned Admin</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->admin?->name ?? 'None' }}</span>
                    </div>
                    @if($user->role !== 'admin')
                        <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                            <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Leave Balance</span>
                            <span class="text-sm font-semibold text-brass font-mono">{{ number_format($user->leave_balance, 2) }} days</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- CONTACT -->
            <div id="contact" x-show="activeSection === 'contact'" class="scroll-mt-6 border-b border-hairline pb-8">
                <h4 class="text-sm font-semibold text-brass uppercase tracking-wider mb-4">Contact & Address</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Current Address -->
                    <div>
                        <h5 class="text-xs font-bold text-vellum uppercase tracking-wider mb-3">Current Address</h5>
                        <div class="flex flex-col">
                            <div class="grid grid-cols-[150px_1fr] py-2 border-b border-hairline last:border-none items-center">
                                <span class="text-[11px] font-semibold text-vellum-faint uppercase tracking-wider">Line 1</span>
                                <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->current_address1 ?? 'N/A' }}</span>
                            </div>
                            <div class="grid grid-cols-[150px_1fr] py-2 border-b border-hairline last:border-none items-center">
                                <span class="text-[11px] font-semibold text-vellum-faint uppercase tracking-wider">Line 2</span>
                                <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->current_address2 ?? 'N/A' }}</span>
                            </div>
                            <div class="grid grid-cols-[150px_1fr] py-2 border-b border-hairline last:border-none items-center">
                                <span class="text-[11px] font-semibold text-vellum-faint uppercase tracking-wider">City</span>
                                <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->current_city ?? 'N/A' }}</span>
                            </div>
                            <div class="grid grid-cols-[150px_1fr] py-2 border-b border-hairline last:border-none items-center">
                                <span class="text-[11px] font-semibold text-vellum-faint uppercase tracking-wider">State</span>
                                <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->current_state ?? 'N/A' }}</span>
                            </div>
                            <div class="grid grid-cols-[150px_1fr] py-2 border-b border-hairline last:border-none items-center">
                                <span class="text-[11px] font-semibold text-vellum-faint uppercase tracking-wider">Country</span>
                                <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->current_country ?? 'N/A' }}</span>
                            </div>
                            <div class="grid grid-cols-[150px_1fr] py-2 border-b border-hairline last:border-none items-center">
                                <span class="text-[11px] font-semibold text-vellum-faint uppercase tracking-wider">Zip Code</span>
                                <span class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->current_zip ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Permanent Address -->
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <h5 class="text-xs font-bold text-vellum uppercase tracking-wider">Permanent Address</h5>
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono uppercase {{ $user->employeeProfile?->same_as_current_address ? 'bg-forest-bg text-forest border border-forest/10' : 'bg-surface-raised text-vellum-muted border border-hairline' }}">
                                {{ $user->employeeProfile?->same_as_current_address ? 'Same as Current' : 'Separate' }}
                            </span>
                        </div>
                        <div class="flex flex-col">
                            <div class="grid grid-cols-[150px_1fr] py-2 border-b border-hairline last:border-none items-center">
                                <span class="text-[11px] font-semibold text-vellum-faint uppercase tracking-wider">Line 1</span>
                                <span class="text-sm font-medium text-vellum">
                                    {{ $user->employeeProfile?->same_as_current_address ? ($user->employeeProfile?->current_address1 ?? 'N/A') : ($user->employeeProfile?->permanent_address1 ?? 'N/A') }}
                                </span>
                            </div>
                            <div class="grid grid-cols-[150px_1fr] py-2 border-b border-hairline last:border-none items-center">
                                <span class="text-[11px] font-semibold text-vellum-faint uppercase tracking-wider">Line 2</span>
                                <span class="text-sm font-medium text-vellum">
                                    {{ $user->employeeProfile?->same_as_current_address ? ($user->employeeProfile?->current_address2 ?? 'N/A') : ($user->employeeProfile?->permanent_address2 ?? 'N/A') }}
                                </span>
                            </div>
                            <div class="grid grid-cols-[150px_1fr] py-2 border-b border-hairline last:border-none items-center">
                                <span class="text-[11px] font-semibold text-vellum-faint uppercase tracking-wider">City</span>
                                <span class="text-sm font-medium text-vellum">
                                    {{ $user->employeeProfile?->same_as_current_address ? ($user->employeeProfile?->current_city ?? 'N/A') : ($user->employeeProfile?->permanent_city ?? 'N/A') }}
                                </span>
                            </div>
                            <div class="grid grid-cols-[150px_1fr] py-2 border-b border-hairline last:border-none items-center">
                                <span class="text-[11px] font-semibold text-vellum-faint uppercase tracking-wider">State</span>
                                <span class="text-sm font-medium text-vellum">
                                    {{ $user->employeeProfile?->same_as_current_address ? ($user->employeeProfile?->current_state ?? 'N/A') : ($user->employeeProfile?->permanent_state ?? 'N/A') }}
                                </span>
                            </div>
                            <div class="grid grid-cols-[150px_1fr] py-2 border-b border-hairline last:border-none items-center">
                                <span class="text-[11px] font-semibold text-vellum-faint uppercase tracking-wider">Country</span>
                                <span class="text-sm font-medium text-vellum">
                                    {{ $user->employeeProfile?->same_as_current_address ? ($user->employeeProfile?->current_country ?? 'N/A') : ($user->employeeProfile?->permanent_country ?? 'N/A') }}
                                </span>
                            </div>
                            <div class="grid grid-cols-[150px_1fr] py-2 border-b border-hairline last:border-none items-center">
                                <span class="text-[11px] font-semibold text-vellum-faint uppercase tracking-wider">Zip Code</span>
                                <span class="text-sm font-medium text-vellum font-mono">
                                    {{ $user->employeeProfile?->same_as_current_address ? ($user->employeeProfile?->current_zip ?? 'N/A') : ($user->employeeProfile?->permanent_zip ?? 'N/A') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- EMERGENCY CONTACTS -->
            <div id="emergency" x-show="activeSection === 'emergency'" class="scroll-mt-6 border-b border-hairline pb-8">
                <h4 class="text-sm font-semibold text-brass uppercase tracking-wider mb-4">Emergency Contact</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-1">
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Contact Name</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->emergency_name ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Relationship</span>
                        <span class="text-sm font-medium text-vellum capitalize">{{ $user->employeeProfile?->emergency_relationship ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Email Address</span>
                        <span class="text-sm font-medium text-vellum select-all">{{ $user->employeeProfile?->emergency_email ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Mobile Phone</span>
                        <span class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->emergency_mobile ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center md:col-span-2">
                        <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Address</span>
                        <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->emergency_address ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>

            <!-- BANKING & REGISTRY -->
            <!-- PAYROLL & LEAVES -->
            <div id="payroll" x-show="activeSection === 'payroll'" class="scroll-mt-6 space-y-8 border-b border-hairline pb-8">
                <!-- Payroll Information -->
                <div>
                    <h4 class="text-sm font-semibold text-brass uppercase tracking-wider mb-4 font-display">Payroll Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-1">
                        <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                            <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Base Salary</span>
                            <span class="text-sm font-medium text-vellum font-mono">
                                {{ $user->payrollProfile?->base_salary !== null ? '₹ ' . number_format($user->payrollProfile->base_salary, 2) : 'N/A' }}
                            </span>
                        </div>
                        <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                            <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Salary Effective Date</span>
                            <span class="text-sm font-medium text-vellum font-mono">
                                {{ $user->payrollProfile?->salary_effective_date?->format('M d, Y') ?? 'N/A' }}
                            </span>
                        </div>
                        <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                            <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Payroll Enabled</span>
                            <span class="text-sm font-medium text-vellum">
                                @if($user->payrollProfile?->payroll_enabled)
                                    <span class="px-2 py-0.5 text-[9.5px] font-mono font-semibold uppercase tracking-[0.5px] rounded bg-forest-bg text-forest border border-transparent">
                                        Enabled
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 text-[9.5px] font-mono font-semibold uppercase tracking-[0.5px] rounded bg-surface-raised text-vellum-muted border border-hairline">
                                        Disabled
                                    </span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Detailed Leave Balances -->
                <div>
                    <h4 class="text-sm font-semibold text-brass uppercase tracking-wider mb-4 font-display">Detailed Leave Balances</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-1">
                        <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                            <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Planned Leave</span>
                            <span class="text-sm font-medium text-vellum font-mono">{{ number_format($user->leaveBalance?->planned_leave ?? 0.00, 2) }} days</span>
                        </div>
                        <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                            <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Birthday Leave</span>
                            <span class="text-sm font-medium text-vellum font-mono">{{ number_format($birthdayBalance, 2) }} days</span>
                        </div>
                        <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                            <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Unplanned Leave</span>
                            <span class="text-sm font-medium text-vellum font-mono">{{ number_format($user->leaveBalance?->unplanned_leave ?? 0.00, 2) }} days</span>
                        </div>
                        <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                            <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Paternity Leave</span>
                            <span class="text-sm font-medium text-vellum font-mono">{{ number_format($user->leaveBalance?->paternity_leave ?? 0.00, 2) }} days</span>
                        </div>
                        <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                            <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Maternity Leave</span>
                            <span class="text-sm font-medium text-vellum font-mono">{{ number_format($user->leaveBalance?->maternity_leave ?? 0.00, 2) }} days</span>
                        </div>
                        <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                            <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Compensatory Leave</span>
                            <span class="text-sm font-medium text-vellum font-mono">{{ number_format($user->leaveBalance?->compensatory_leave ?? 0.00, 2) }} days</span>
                        </div>
                        <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                            <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Carry Forward</span>
                            <span class="text-sm font-medium text-vellum font-mono">{{ number_format($user->leaveBalance?->carry_forward ?? 0.00, 2) }} days</span>
                        </div>
                        <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                            <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider font-bold">Total Allocation</span>
                            <span class="text-sm font-medium text-vellum font-mono font-bold">{{ number_format($user->leaveBalance?->total_leave ?? 0.00, 2) }} days</span>
                        </div>
                        <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                            <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Pending Leave Requests</span>
                            <span class="text-sm font-medium text-vellum font-mono">{{ number_format($user->leaveBalance?->pending_leave ?? 0.00, 2) }} days</span>
                        </div>
                        <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                            <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Utilized Leave</span>
                            <span class="text-sm font-medium text-vellum font-mono">{{ number_format($user->leaveBalance?->utilized_leave ?? 0.00, 2) }} days</span>
                        </div>
                        <div class="grid grid-cols-[200px_1fr] py-3 border-b border-hairline last:border-none items-center">
                            <span class="text-xs font-semibold text-brass uppercase tracking-wider font-bold">Remaining Leave</span>
                            <span class="text-sm font-bold text-brass font-mono">{{ number_format($user->leaveBalance?->remaining_leave ?? 0.00, 2) }} days</span>
                        </div>
                    </div>
                </div>

                <!-- Banking & Registries -->
                <div>
                    <h4 class="text-sm font-semibold text-brass uppercase tracking-wider mb-4 font-display">Banking & Government Registries</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-1">
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
            </div>

            <!-- FUTURE TIMELINE -->
            <div id="timeline" x-show="activeSection === 'timeline'" class="scroll-mt-6 border-b border-hairline pb-8">
                <div class="flex justify-between items-center mb-6">
                    <h4 class="text-sm font-semibold text-brass uppercase tracking-wider">Future Timeline</h4>
                    @if(auth()->user()->role === 'admin')
                        <x-primary-button @click="$dispatch('open-modal', 'add-timeline-modal')" class="!h-[32px] text-xs">
                            + Add Milestone
                        </x-primary-button>
                    @endif
                </div>

                <div class="relative pl-6 border-l-2 border-hairline space-y-6">
                    <!-- Milestone: Birthday -->
                    @if($user->employeeProfile?->date_of_birth)
                        <div class="relative">
                            <div class="absolute -left-[31px] top-1.5 w-[10px] h-[10px] rounded-full border-2 border-brass bg-canvas"></div>
                            <div>
                                <span class="text-xs font-mono font-semibold text-brass">{{ $user->employeeProfile->date_of_birth->format('F d') }}</span>
                                <h5 class="text-sm font-semibold text-vellum mt-0.5">Upcoming Birthday</h5>
                                <p class="text-xs text-vellum-muted mt-0.5">Annual celebration milestone. Birthday leave eligibility is dynamically calculated.</p>
                            </div>
                        </div>
                    @endif

                    <!-- Milestone: Service Anniversary -->
                    @if($user->joining_date)
                        <div class="relative">
                            <div class="absolute -left-[31px] top-1.5 w-[10px] h-[10px] rounded-full border-2 border-brass bg-canvas"></div>
                            <div>
                                <span class="text-xs font-mono font-semibold text-brass">{{ $user->joining_date->format('F d') }}</span>
                                <h5 class="text-sm font-semibold text-vellum mt-0.5">Service Anniversary</h5>
                                <p class="text-xs text-vellum-muted mt-0.5">Celebration of employee's original joining date ({{ $user->joining_date->format('Y-m-d') }}).</p>
                            </div>
                        </div>
                    @endif

                    <!-- Milestone: Contract Renewal / End -->
                    @if($user->employeeProfile?->contract_end_date)
                        <div class="relative">
                            <div class="absolute -left-[31px] top-1.5 w-[10px] h-[10px] rounded-full border-2 border-brass bg-canvas"></div>
                            <div>
                                <span class="text-xs font-mono font-semibold text-brass">{{ $user->employeeProfile->contract_end_date->format('M d, Y') }}</span>
                                <h5 class="text-sm font-semibold text-vellum mt-0.5">Contract End / Renewal Date</h5>
                                <p class="text-xs text-vellum-muted mt-0.5">Scheduled tenure checkpoint and contract validation review.</p>
                            </div>
                        </div>
                    @endif

                    <!-- Milestone: Annual Review -->
                    <div class="relative">
                        <div class="absolute -left-[31px] top-1.5 w-[10px] h-[10px] rounded-full border-2 border-brass bg-canvas"></div>
                        <div>
                            <span class="text-xs font-mono font-semibold text-brass">October 01, 2026</span>
                            <h5 class="text-sm font-semibold text-vellum mt-0.5">Annual Performance Audit</h5>
                            <p class="text-xs text-vellum-muted mt-0.5">System-wide workforce review and structural ledger evaluation.</p>
                        </div>
                    </div>

                    <!-- Manual Milestone Entries -->
                    @foreach($user->manualTimelineEntries()->orderBy('entry_date')->get() as $entry)
                        <div class="relative">
                            <div class="absolute -left-[31px] top-1.5 w-[10px] h-[10px] rounded-full border-2 border-cognac bg-canvas"></div>
                            <div class="flex items-start justify-between">
                                <div>
                                    <span class="text-xs font-mono font-semibold text-cognac">{{ $entry->entry_date->format('M d, Y') }}</span>
                                    <h5 class="text-sm font-semibold text-vellum mt-0.5">{{ $entry->title }}</h5>
                                    @if($entry->description)
                                        <p class="text-xs text-vellum-muted mt-0.5">{{ $entry->description }}</p>
                                    @endif
                                    <span class="inline-block mt-1 text-[9.5px] font-semibold text-cognac/80 uppercase font-mono tracking-wider">Manual Milestone</span>
                                </div>
                                @if(auth()->user()->role === 'admin')
                                    <div class="flex items-center gap-2">
                                        <button @click="$dispatch('open-modal', 'edit-timeline-modal-{{ $entry->id }}')" class="text-xs text-brass hover:text-brass-bright font-semibold">Edit</button>
                                        <form method="POST" action="{{ route('admin.timeline.destroy', $entry) }}" onsubmit="return confirm('Are you sure you want to delete this milestone?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-burgundy-light hover:text-burgundy font-semibold">Delete</button>
                                        </form>

                                        <!-- Edit Milestone Modal -->
                                        <x-modal name="edit-timeline-modal-{{ $entry->id }}" :show="false">
                                            <form method="POST" action="{{ route('admin.timeline.update', $entry) }}" class="p-6">
                                                @csrf
                                                @method('PUT')
                                                <h2 class="font-display font-medium text-lg text-vellum mb-4">Edit Manual Timeline Milestone</h2>
                                                <div class="mb-4">
                                                    <x-input-label for="title_{{ $entry->id }}" value="Milestone Title" />
                                                    <input type="text" name="title" id="title_{{ $entry->id }}" value="{{ $entry->title }}" required class="w-full bg-canvas border border-hairline text-vellum rounded px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                                                </div>
                                                <div class="mb-4">
                                                    <x-input-label for="entry_date_{{ $entry->id }}" value="Milestone Date" />
                                                    <input type="date" name="entry_date" id="entry_date_{{ $entry->id }}" value="{{ $entry->entry_date->format('Y-m-d') }}" required class="w-full bg-canvas border border-hairline text-vellum rounded px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                                                </div>
                                                <div class="mb-6">
                                                    <x-input-label for="description_{{ $entry->id }}" value="Description (Optional)" />
                                                    <textarea name="description" id="description_{{ $entry->id }}" rows="3" class="w-full bg-canvas border border-hairline text-vellum rounded px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">{{ $entry->description }}</textarea>
                                                </div>
                                                <div class="flex justify-end gap-2.5">
                                                    <x-secondary-button x-on:click="$dispatch('close-modal', 'edit-timeline-modal-{{ $entry->id }}')">Cancel</x-secondary-button>
                                                    <x-primary-button type="submit">Save Changes</x-primary-button>
                                                </div>
                                            </form>
                                        </x-modal>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- CAREER HISTORY -->
            <div id="history" x-show="activeSection === 'history'" class="scroll-mt-6 border-b border-hairline pb-8">
                <h4 class="text-sm font-semibold text-brass uppercase tracking-wider mb-4">Academic & Career History</h4>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-6">
                    <!-- Academic Credentials -->
                    <div>
                        <h5 class="text-xs font-bold text-vellum uppercase tracking-wider mb-3">Academic Credentials</h5>
                        <div class="flex flex-col">
                            <div class="grid grid-cols-[150px_1fr] py-2 border-b border-hairline last:border-none items-center">
                                <span class="text-[11px] font-semibold text-vellum-faint uppercase tracking-wider">Degree Name</span>
                                <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->degree_name ?? 'N/A' }}</span>
                            </div>
                            <div class="grid grid-cols-[150px_1fr] py-2 border-b border-hairline last:border-none items-center">
                                <span class="text-[11px] font-semibold text-vellum-faint uppercase tracking-wider">Institution</span>
                                <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->institution_name ?? 'N/A' }}</span>
                            </div>
                            <div class="grid grid-cols-[150px_1fr] py-2 border-b border-hairline last:border-none items-center">
                                <span class="text-[11px] font-semibold text-vellum-faint uppercase tracking-wider">Passing Year</span>
                                <span class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->passing_year ?? 'N/A' }}</span>
                            </div>
                            <div class="grid grid-cols-[150px_1fr] py-2 border-b border-hairline last:border-none items-center">
                                <span class="text-[11px] font-semibold text-vellum-faint uppercase tracking-wider">Score</span>
                                <span class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->percentage ? ($user->employeeProfile->percentage . '%') : 'N/A' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Previous Employment -->
                    <div>
                        <h5 class="text-xs font-bold text-vellum uppercase tracking-wider mb-3">Previous Employment</h5>
                        <div class="flex flex-col">
                            <div class="grid grid-cols-[150px_1fr] py-2 border-b border-hairline last:border-none items-center">
                                <span class="text-[11px] font-semibold text-vellum-faint uppercase tracking-wider">Company</span>
                                <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->previous_company_name ?? 'N/A' }}</span>
                            </div>
                            <div class="grid grid-cols-[150px_1fr] py-2 border-b border-hairline last:border-none items-center">
                                <span class="text-[11px] font-semibold text-vellum-faint uppercase tracking-wider">Job Title</span>
                                <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->previous_job_title ?? 'N/A' }}</span>
                            </div>
                            <div class="grid grid-cols-[150px_1fr] py-2 border-b border-hairline last:border-none items-center">
                                <span class="text-[11px] font-semibold text-vellum-faint uppercase tracking-wider">From Date</span>
                                <span class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->previous_from_date?->format('M d, Y') ?? 'N/A' }}</span>
                            </div>
                            <div class="grid grid-cols-[150px_1fr] py-2 border-b border-hairline last:border-none items-center">
                                <span class="text-[11px] font-semibold text-vellum-faint uppercase tracking-wider">To Date</span>
                                <span class="text-sm font-medium text-vellum font-mono">{{ $user->employeeProfile?->previous_to_date?->format('M d, Y') ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-hairline pt-6">
                    <h5 class="text-xs font-bold text-vellum uppercase tracking-wider mb-3">Tenure & Performance</h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-1">
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
                            <span class="text-xs font-semibold text-vellum-faint uppercase tracking-wider">Verification Source</span>
                            <span class="text-sm font-medium text-vellum">{{ $user->employeeProfile?->source_of_verification ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PROFILE CORRECTION REQUESTS -->
            @if(auth()->user()->id === $user->id || auth()->user()->role === 'admin')
                <div id="corrections" x-show="activeSection === 'corrections'" class="scroll-mt-6">
                    <div class="flex justify-between items-center mb-6">
                        <h4 class="text-sm font-semibold text-brass uppercase tracking-wider">Profile Correction Requests</h4>
                        @if(auth()->user()->role === 'admin')
                            <x-primary-button @click="$dispatch('open-modal', 'add-correction-modal')" class="!h-[32px] text-xs">
                                + Add Correction
                            </x-primary-button>
                        @endif
                    </div>

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

                                    @if(auth()->user()->role === 'admin')
                                        <div class="mt-4 pt-2 border-t border-dashed border-hairline flex items-center gap-3">
                                            <button @click="$dispatch('open-modal', 'edit-correction-modal-{{ $req->id }}')" class="text-xs text-brass hover:text-brass-bright font-semibold">Edit Record</button>
                                            <form method="POST" action="{{ route('admin.corrections.destroy', $req) }}" onsubmit="return confirm('Are you sure you want to delete this correction request?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs text-burgundy-light hover:text-burgundy font-semibold">Delete Record</button>
                                            </form>
                                        </div>

                                        <!-- Edit Correction Modal -->
                                        <x-modal name="edit-correction-modal-{{ $req->id }}" :show="false">
                                            <form method="POST" action="{{ route('admin.corrections.update', $req) }}" class="p-6">
                                                @csrf
                                                @method('PUT')
                                                <h2 class="font-display font-medium text-lg text-vellum mb-4">Edit Correction Request</h2>
                                                
                                                <div class="mb-4">
                                                    <x-input-label for="field_{{ $req->id }}" value="Field to Correct" />
                                                    <select name="field" id="field_{{ $req->id }}" required class="w-full bg-canvas border border-hairline text-vellum rounded px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                                                        @foreach(['Phone Number', 'Personal Email', 'Official Email', 'Department', 'Designation', 'Reporting Manager', 'Joining Date', 'Address', 'Bank Details', 'Emergency Contact', 'Other'] as $f)
                                                            <option value="{{ $f }}" {{ $req->field === $f ? 'selected' : '' }}>{{ $f }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="mb-4">
                                                    <x-input-label for="message_{{ $req->id }}" value="Reason / Details" />
                                                    <textarea name="message" id="message_{{ $req->id }}" rows="3" required minlength="5" maxlength="1000" class="w-full bg-canvas border border-hairline text-vellum rounded px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">{{ $req->message }}</textarea>
                                                </div>

                                                <div class="mb-4">
                                                    <x-input-label for="status_{{ $req->id }}" value="Status" />
                                                    <select name="status" id="status_{{ $req->id }}" required class="w-full bg-canvas border border-hairline text-vellum rounded px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                                                        <option value="pending" {{ $req->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                                        <option value="resolved" {{ $req->status === 'resolved' ? 'selected' : '' }}>Resolved</option>
                                                    </select>
                                                </div>

                                                <div class="mb-6">
                                                    <x-input-label for="admin_note_{{ $req->id }}" value="Admin Note" />
                                                    <textarea name="admin_note" id="admin_note_{{ $req->id }}" rows="3" class="w-full bg-canvas border border-hairline text-vellum rounded px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">{{ $req->admin_note }}</textarea>
                                                </div>

                                                <div class="flex justify-end gap-2.5">
                                                    <x-secondary-button x-on:click="$dispatch('close-modal', 'edit-correction-modal-{{ $req->id }}')">Cancel</x-secondary-button>
                                                    <x-primary-button type="submit">Save Changes</x-primary-button>
                                                </div>
                                            </form>
                                        </x-modal>
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

    @if(auth()->user()->role === 'admin')
    <!-- Add Timeline Milestone Modal -->
    <x-modal name="add-timeline-modal" :show="false">
        <form method="POST" action="{{ route('admin.timeline.store', $user) }}" class="p-6">
            @csrf
            <h2 class="font-display font-medium text-lg text-vellum mb-4">Add Manual Timeline Milestone</h2>
            <div class="mb-4">
                <x-input-label for="add_title" value="Milestone Title" />
                <input type="text" name="title" id="add_title" required placeholder="e.g. Promotion, Transfer" class="w-full bg-canvas border border-hairline text-vellum rounded px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
            </div>
            <div class="mb-4">
                <x-input-label for="add_entry_date" value="Milestone Date" />
                <input type="date" name="entry_date" id="add_entry_date" required class="w-full bg-canvas border border-hairline text-vellum rounded px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
            </div>
            <div class="mb-6">
                <x-input-label for="add_description" value="Description (Optional)" />
                <textarea name="description" id="add_description" rows="3" placeholder="Additional context details..." class="w-full bg-canvas border border-hairline text-vellum rounded px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none"></textarea>
            </div>
            <div class="flex justify-end gap-2.5">
                <x-secondary-button x-on:click="$dispatch('close-modal', 'add-timeline-modal')">Cancel</x-secondary-button>
                <x-primary-button type="submit">Add Milestone</x-primary-button>
            </div>
        </form>
    </x-modal>

    <!-- Add Correction Modal -->
    <x-modal name="add-correction-modal" :show="false">
        <form method="POST" action="{{ route('admin.corrections.store', $user) }}" class="p-6">
            @csrf
            <h2 class="font-display font-medium text-lg text-vellum mb-4">Add Correction Entry</h2>
            
            <div class="mb-4">
                <x-input-label for="add_c_field" value="Field to Correct" />
                <select name="field" id="add_c_field" required class="w-full bg-canvas border border-hairline text-vellum rounded px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
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
            </div>

            <div class="mb-4">
                <x-input-label for="add_c_message" value="Reason / Details (Required)" />
                <textarea name="message" id="add_c_message" rows="3" required minlength="5" maxlength="1000" placeholder="Please specify the reason/details..." class="w-full bg-canvas border border-hairline text-vellum rounded px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none"></textarea>
            </div>

            <div class="mb-4">
                <x-input-label for="add_c_status" value="Status" />
                <select name="status" id="add_c_status" required class="w-full bg-canvas border border-hairline text-vellum rounded px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none">
                    <option value="pending">Pending</option>
                    <option value="resolved">Resolved</option>
                </select>
            </div>

            <div class="mb-6">
                <x-input-label for="add_c_admin_note" value="Admin Note (Optional)" />
                <textarea name="admin_note" id="add_c_admin_note" rows="3" placeholder="Administrative resolution remarks..." class="w-full bg-canvas border border-hairline text-vellum rounded px-3 py-2 text-sm focus:ring-1 focus:ring-brass focus:border-brass focus:outline-none"></textarea>
            </div>

            <div class="flex justify-end gap-2.5">
                <x-secondary-button x-on:click="$dispatch('close-modal', 'add-correction-modal')">Cancel</x-secondary-button>
                <x-primary-button type="submit">Save Entry</x-primary-button>
            </div>
        </form>
    </x-modal>
    @endif
