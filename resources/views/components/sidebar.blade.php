<aside class="fixed top-0 left-0 h-screen w-64 bg-surface dark:bg-surface glass-panel rounded-none border-r border-primary/10 z-50 flex flex-col">

    <!-- Logo Section -->
    <div class="h-16 flex items-center px-gutter border-b border-outline-variant/30">
        <div class="flex items-center gap-stack-sm">
            <div class="w-8 h-8 rounded-md bg-gradient-to-br from-primary to-primary-fixed flex items-center justify-center">
                <span class="text-white font-bold text-sm">AMS</span>
            </div>
            <h1 class="text-on-surface font-semibold text-lg tracking-tight">
                AMS
            </h1>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-stack-sm py-stack-lg space-y-unit overflow-y-auto scroll-hide">

        @if(auth()->user()->role === 'employee')
            <!-- Employee Navigation -->

            <!-- Dashboard -->
            <a href="{{ route('employee.dashboard') }}"
               class="flex items-center gap-stack-md px-stack-md py-stack-sm rounded-md transition-all duration-200
               {{ request()->routeIs('employee.dashboard')
                    ? 'bg-primary text-white shadow-[0_0_10px_rgba(255,45,120,0.3)]'
                    : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface' }}">
                <!-- Home Icon -->
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-3m0 0l7-4 7 4M5 9v10a1 1 0 001 1h2a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h2a1 1 0 001-1V9m-9 4l4-2m0 0l4 2" />
                </svg>
                <span class="font-medium text-sm">Dashboard</span>
            </a>

            <!-- Attendance History -->
            <a href="{{ route('attendance.history') }}"
               class="flex items-center gap-stack-md px-stack-md py-stack-sm rounded-md transition-all duration-200
               {{ request()->routeIs('attendance.history')
                    ? 'bg-primary text-white shadow-[0_0_10px_rgba(255,45,120,0.3)]'
                    : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface' }}">
                <!-- Calendar Icon -->
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span class="font-medium text-sm">Attendance History</span>
            </a>

        @else
            <!-- Manager/Admin Navigation -->

            <!-- Dashboard -->
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-stack-md px-stack-md py-stack-sm rounded-md transition-all duration-200
               {{ request()->routeIs('dashboard')
                    ? 'bg-primary text-white shadow-[0_0_10px_rgba(255,45,120,0.3)]'
                    : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface' }}">
                <!-- Home Icon -->
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-3m0 0l7-4 7 4M5 9v10a1 1 0 001 1h2a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h2a1 1 0 001-1V9m-9 4l4-2m0 0l4 2" />
                </svg>
                <span class="font-medium text-sm">Dashboard</span>
            </a>

            <!-- Employees -->
            <a href="{{ route('employees.index') }}"
               class="flex items-center gap-stack-md px-stack-md py-stack-sm rounded-md transition-all duration-200
               {{ request()->routeIs('employees.*')
                    ? 'bg-primary text-white shadow-[0_0_10px_rgba(255,45,120,0.3)]'
                    : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface' }}">
                <!-- Users Icon -->
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 8.048M9 9h6m-8 4h16M5.5 20h13a2 2 0 002-2v-1a6 6 0 00-9-5.197 6 6 0 00-9 5.197v1a2 2 0 002 2z" />
                </svg>
                <span class="font-medium text-sm">Employees</span>
            </a>

            <!-- Departments -->
            <a href="{{ route('departments.index') }}"
               class="flex items-center gap-stack-md px-stack-md py-stack-sm rounded-md transition-all duration-200
               {{ request()->routeIs('departments.*')
                    ? 'bg-primary text-white shadow-[0_0_10px_rgba(255,45,120,0.3)]'
                    : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface' }}">
                <!-- Building Icon -->
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z" />
                </svg>
                <span class="font-medium text-sm">Departments</span>
            </a>

        @endif

    </nav>

    <!-- Settings Section -->
    <div class="px-stack-sm py-stack-md border-t border-outline-variant/30">
        <a href="{{ route('profile.edit') }}"
           class="flex items-center gap-stack-md px-stack-md py-stack-sm rounded-md transition-all duration-200 text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface
           {{ request()->routeIs('profile.*') ? 'bg-primary text-white shadow-[0_0_10px_rgba(255,45,120,0.3)]' : '' }}">
            <!-- Settings Icon -->
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span class="font-medium text-sm">Settings</span>
        </a>
    </div>

</aside>

<!-- Mobile Menu Toggle (Alpine.js ready for future) -->
<div class="md:hidden fixed top-4 left-4 z-40">
    <button id="mobile-menu-toggle"
            class="p-2 rounded-md bg-surface-container text-secondary hover:bg-surface-container-high transition-colors">
        <!-- Menu Icon -->
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>
</div>