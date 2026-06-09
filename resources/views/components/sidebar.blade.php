<aside class="fixed top-0 left-0 h-screen w-[260px] bg-slate-900 shadow-lg z-50">

    <!-- Logo -->
    <div class="h-16 flex items-center justify-center border-b border-slate-800">
        <h1 class="text-white text-xl font-bold tracking-wide">
            AMS
        </h1>
    </div>

    <!-- Navigation -->
    <nav class="mt-6 px-4">

        <ul class="space-y-2">

            <li>
                <a href="{{ route('dashboard') }}"
                   class="flex items-center px-4 py-3 rounded-lg font-medium transition
                   {{ request()->routeIs('dashboard')
                        ? 'bg-indigo-600 text-white'
                        : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                    Dashboard
                </a>
            </li>

            <li>
                <a href="{{ route('employees.index') }}"
                   class="flex items-center px-4 py-3 rounded-lg font-medium transition
                   {{ request()->routeIs('employees.*')
                        ? 'bg-indigo-600 text-white'
                        : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                    Employees
                </a>
            </li>

            <li>
                <a href="{{ route('departments.index') }}"
                   class="flex items-center px-4 py-3 rounded-lg font-medium transition
                   {{ request()->routeIs('departments.*')
                        ? 'bg-indigo-600 text-white'
                        : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                    Departments
                </a>
            </li>

            <li>
                <a href="#"
                   class="flex items-center px-4 py-3 rounded-lg font-medium text-slate-200 hover:bg-slate-800 hover:text-white transition">
                    Attendance
                </a>
            </li>

            <li>
                <a href="#"
                   class="flex items-center px-4 py-3 rounded-lg font-medium text-slate-200 hover:bg-slate-800 hover:text-white transition">
                    Reports
                </a>
            </li>

            <li>
                <a href="#"
                   class="flex items-center px-4 py-3 rounded-lg font-medium text-slate-200 hover:bg-slate-800 hover:text-white transition">
                    Settings
                </a>
            </li>

        </ul>

    </nav>

</aside>