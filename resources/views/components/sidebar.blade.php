@php
    $user = Auth::user();
    $role = $user->role;
    
    // Resolve user initials for the avatar
    $words = explode(' ', $user->name);
    $initials = '';
    foreach ($words as $word) {
        $initials .= strtoupper(substr($word, 0, 1));
    }
    $initials = substr($initials, 0, 2);
@endphp

<aside class="sidebar w-[260px] shrink-0 bg-walnut border-r border-hairline flex flex-col py-7 sticky top-0 h-screen">
    <!-- Crest Section -->
    <div class="crest flex items-center gap-3 px-6 pb-7 mb-2 border-b border-hairline/10">
        <div class="crest-mark w-[36px] h-[36px] border border-brass-bright rounded-full flex items-center justify-center font-display font-semibold text-[15px] text-brass-bright tracking-wider">
            VQ
        </div>
        <div class="crest-text leading-tight">
            <div class="top font-display text-[15px] font-semibold text-vellum-light tracking-wide">Workforce</div>
            <div class="bottom text-[11px] text-vellum-light-muted tracking-[1.8px] uppercase mt-0.5">Ledger</div>
        </div>
    </div>

    <!-- Navigation List -->
    <nav class="flex-1 px-3.5 py-5 space-y-1.5">
        <!-- Dashboard Link (All Roles) -->
        @php
            $dashboardRoute = ($role === 'employee') ? route('employee.dashboard') : route('dashboard');
            $isDashboardActive = request()->routeIs('dashboard') || request()->routeIs('employee.dashboard');
        @endphp
        <a href="{{ $dashboardRoute }}" 
           class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-md text-[14.5px] font-medium transition duration-150 ease-in-out border border-transparent
           {{ $isDashboardActive 
               ? 'bg-brass/[0.12] text-brass-bright font-semibold border-l-[3px] border-l-brass-bright border-y-transparent border-r-transparent' 
               : 'text-vellum-light-muted hover:bg-brass/[0.04] hover:text-vellum-light' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="w-4 h-4 flex-shrink-0 {{ $isDashboardActive ? 'opacity-100' : 'opacity-75' }}">
                <rect x="3" y="3" width="8" height="8" rx="1.5"/>
                <rect x="13" y="3" width="8" height="8" rx="1.5"/>
                <rect x="3" y="13" width="8" height="8" rx="1.5"/>
                <rect x="13" y="13" width="8" height="8" rx="1.5"/>
            </svg>
            Dashboard
        </a>

        <!-- Attendance Logs Link (Admin Only) - Moved here directly below Dashboard -->
        @if($role === 'admin')
            @php
                $isAttendanceLogsActive = request()->routeIs('admin.attendance.logs');
            @endphp
            <a href="{{ route('admin.attendance.logs') }}" 
               class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-md text-[14.5px] font-medium transition duration-150 ease-in-out border border-transparent
               {{ $isAttendanceLogsActive 
                   ? 'bg-brass/[0.12] text-brass-bright font-semibold border-l-[3px] border-l-brass-bright border-y-transparent border-r-transparent' 
                   : 'text-vellum-light-muted hover:bg-brass/[0.04] hover:text-vellum-light' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="w-4 h-4 flex-shrink-0 {{ $isAttendanceLogsActive ? 'opacity-100' : 'opacity-75' }}">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                <span>Attendance Logs</span>
            </a>
        @endif

        <!-- My Attendance Link (All Roles) -->
        @php
            $isAttendanceActive = request()->routeIs('attendance.my-attendance');
        @endphp
        <a href="{{ route('attendance.my-attendance') }}" 
           class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-md text-[14.5px] font-medium transition duration-150 ease-in-out border border-transparent
           {{ $isAttendanceActive 
               ? 'bg-brass/[0.12] text-brass-bright font-semibold border-l-[3px] border-l-brass-bright border-y-transparent border-r-transparent' 
               : 'text-vellum-light-muted hover:bg-brass/[0.04] hover:text-vellum-light' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="w-4 h-4 flex-shrink-0 {{ $isAttendanceActive ? 'opacity-100' : 'opacity-75' }}">
                <circle cx="12" cy="12" r="9"/>
                <path d="M12 7v5l3.5 2"/>
            </svg>
            My Attendance
        </a>

        <!-- Workforce Link (Admins and Managers) -->
        @if($role === 'admin' || $role === 'manager')
            @php
                $isWorkforceActive = request()->routeIs('employees.*');
            @endphp
            <a href="{{ route('employees.index') }}" 
               class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-md text-[14.5px] font-medium transition duration-150 ease-in-out border border-transparent
               {{ $isWorkforceActive 
                   ? 'bg-brass/[0.12] text-brass-bright font-semibold border-l-[3px] border-l-brass-bright border-y-transparent border-r-transparent' 
                   : 'text-vellum-light-muted hover:bg-brass/[0.04] hover:text-vellum-light' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="w-4 h-4 flex-shrink-0 {{ $isWorkforceActive ? 'opacity-100' : 'opacity-75' }}">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                Workforce
            </a>
        @endif

        <!-- Departments Link (Admins and Managers) -->
        @if($role === 'admin' || $role === 'manager')
            @php
                $isDepartmentsActive = request()->routeIs('departments.*');
            @endphp
            <a href="{{ route('departments.index') }}" 
               class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-md text-[14.5px] font-medium transition duration-150 ease-in-out border border-transparent
               {{ $isDepartmentsActive 
                   ? 'bg-brass/[0.12] text-brass-bright font-semibold border-l-[3px] border-l-brass-bright border-y-transparent border-r-transparent' 
                   : 'text-vellum-light-muted hover:bg-brass/[0.04] hover:text-vellum-light' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="w-4 h-4 flex-shrink-0 {{ $isDepartmentsActive ? 'opacity-100' : 'opacity-75' }}">
                    <path d="M3 21h18M3 7v14M21 7v14M16 3H8v4h8V3zM12 11h.01M12 15h.01M16 11h.01M16 15h.01M8 11h.01M8 15h.01"/>
                </svg>
                Departments
            </a>
        @endif

        <!-- Leaves Link (All Roles) -->
        @php
            $isLeavesActive = request()->routeIs('leaves.*');
        @endphp
        <a href="{{ route('leaves.index') }}" 
           class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-md text-[14.5px] font-medium transition duration-150 ease-in-out border border-transparent
           {{ $isLeavesActive 
               ? 'bg-brass/[0.12] text-brass-bright font-semibold border-l-[3px] border-l-brass-bright border-y-transparent border-r-transparent' 
               : 'text-vellum-light-muted hover:bg-brass/[0.04] hover:text-vellum-light' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="w-4 h-4 flex-shrink-0 {{ $isLeavesActive ? 'opacity-100' : 'opacity-75' }}">
                <rect x="3" y="4" width="18" height="17" rx="1.5"/>
                <path d="M3 9h18M8 2v4M16 2v4"/>
            </svg>
            Leaves
        </a>

        <!-- Import Employees Link (Admin Only) -->
        @if($role === 'admin')
            @php
                $isImportActive = request()->routeIs('admin.import.*');
            @endphp
            <a href="{{ route('admin.import.show') }}" 
               class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-md text-[14.5px] font-medium transition duration-150 ease-in-out border border-transparent
               {{ $isImportActive 
                   ? 'bg-brass/[0.12] text-brass-bright font-semibold border-l-[3px] border-l-brass-bright border-y-transparent border-r-transparent' 
                   : 'text-vellum-light-muted hover:bg-brass/[0.04] hover:text-vellum-light' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="w-4 h-4 flex-shrink-0 {{ $isImportActive ? 'opacity-100' : 'opacity-75' }}">
                    <path d="M12 3v4M12 17v4M3 12h4M17 12h4M5.6 5.6l2.8 2.8M15.6 15.6l2.8 2.8M18.4 5.6l-2.8 2.8M8.4 15.6l-2.8 2.8"/><circle cx="12" cy="12" r="3"/>
                </svg>
                Import Employees
            </a>
        @endif

        <!-- Correction Requests Link (Admin Only) -->
        @if($role === 'admin')
            @php
                $isCorrectionsActive = request()->routeIs('admin.corrections.*');
                $pendingCorrectionsCount = \App\Models\ProfileCorrectionRequest::where('status', 'pending')->count();
            @endphp
            <a href="{{ route('admin.corrections.index') }}" 
               class="nav-item flex items-center justify-between px-3 py-2.5 rounded-md text-[14.5px] font-medium transition duration-150 ease-in-out border border-transparent
               {{ $isCorrectionsActive 
                   ? 'bg-brass/[0.12] text-brass-bright font-semibold border-l-[3px] border-l-brass-bright border-y-transparent border-r-transparent' 
                   : 'text-vellum-light-muted hover:bg-brass/[0.04] hover:text-vellum-light' }}">
                <div class="flex items-center gap-3">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" class="w-4 h-4 flex-shrink-0 {{ $isCorrectionsActive ? 'opacity-100' : 'opacity-75' }}">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                    <span>Correction Requests</span>
                </div>
                @if($pendingCorrectionsCount > 0)
                    <span class="inline-flex items-center justify-center min-w-[18px] h-[18px] px-1.5 text-[10px] font-bold rounded-full bg-burgundy text-vellum border border-hairline-strong">
                        {{ $pendingCorrectionsCount }}
                    </span>
                @endif
            </a>
        @endif
    </nav>

    <!-- User Information & Logout Chip -->
    <div class="sidebar-foot mt-auto border-t border-hairline/10 pt-4 px-6 flex items-center justify-between gap-2.5">
        <div class="flex items-center gap-2.5 min-w-0">
            @if($user->profile_photo_path)
                <img src="{{ asset('storage/' . $user->profile_photo_path) }}" class="avatar w-[32px] h-[32px] rounded-full object-cover border border-hairline-strong shrink-0" alt="{{ $user->name }}">
            @else
                <div class="avatar w-[32px] h-[32px] rounded-full bg-canvas-dark border border-hairline-strong flex items-center justify-center font-display text-[12px] text-brass-bright shrink-0">
                    {{ $initials }}
                </div>
            @endif
            <div class="min-w-0">
                <div class="name text-[13px] font-medium text-vellum-light leading-tight truncate">{{ $user->name }}</div>
                <div class="role text-[10.5px] text-vellum-light-muted leading-none mt-0.5 capitalize">{{ $role }}</div>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="shrink-0 ml-auto">
            @csrf
            <button type="submit" class="text-vellum-light-muted hover:text-burgundy-light transition-colors p-1" title="Log Out">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
            </button>
        </form>
    </div>
</aside>