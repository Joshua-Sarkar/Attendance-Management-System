<x-executive-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 w-full">
            <div>
                <h1 class="font-display font-medium text-[32px] tracking-wide text-vellum leading-none">Attendance Details</h1>
                <div class="text-[13px] text-vellum-muted mt-2.5 tracking-wide">
                    Ledger profile for {{ $user->name }}
                </div>
            </div>
            <!-- Toolbar Actions aligned with baseline -->
            <div class="flex items-center gap-3 mb-0.5">
                @if(auth()->user()->role === 'admin')
                    <a href="{{ route('admin.attendance.logs') }}" 
                       class="inline-flex items-center justify-center text-[10px] font-semibold uppercase tracking-wider px-4 py-2 bg-surface-raised border border-hairline rounded text-vellum hover:border-brass hover:text-brass transition-all duration-150 h-9 focus:outline-none focus:ring-1 focus:ring-brass">
                        ← Attendance Logs
                    </a>
                @endif
                <a href="{{ route('dashboard') }}" 
                   class="inline-flex items-center justify-center text-[10px] font-semibold uppercase tracking-wider px-4 py-2 bg-surface-raised border border-hairline rounded text-vellum hover:border-brass hover:text-brass transition-all duration-150 h-9 focus:outline-none focus:ring-1 focus:ring-brass">
                    ← Back to Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-4 bg-forest-bg text-forest border border-forest/30 px-4 py-3 rounded text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 bg-burgundy-bg text-burgundy border border-burgundy/30 px-4 py-3 rounded text-sm font-medium">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Employee Profile Summary Card -->
    <div class="panel p-6 sm:p-8 w-full border border-hairline bg-surface rounded-lg">
        <div class="flex flex-col lg:flex-row gap-8 lg:gap-12 items-stretch">
            <!-- Identity Section -->
            <div class="flex items-center gap-4 lg:w-[30%] pr-0 lg:pr-8 border-b lg:border-b-0 lg:border-r border-hairline/60 pb-6 lg:pb-0 flex-shrink-0">
                <!-- Circular Avatar Placeholder -->
                <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-[#1E1611]/5 border border-brass flex items-center justify-center flex-shrink-0 select-none">
                    <span class="font-display font-medium text-lg sm:text-xl text-vellum">
                        @php
                            $words = explode(' ', $user->name);
                            $initials = count($words) > 1 
                                ? mb_substr($words[0], 0, 1) . mb_substr(end($words), 0, 1)
                                : mb_substr($user->name, 0, 2);
                            echo strtoupper($initials);
                        @endphp
                    </span>
                </div>
                <!-- Details -->
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-display font-semibold text-2xl sm:text-3xl tracking-wide text-vellum leading-tight">
                            {{ $user->name }}
                        </h2>
                        <span class="tag {{ $user->status === 'active' ? 'present' : 'absent' }} !text-[9px] uppercase font-mono tracking-[0.8px] px-2 py-0.5 rounded">
                            {{ $user->status }}
                        </span>
                    </div>
                    <span class="text-brass font-bold font-mono text-[13px] block mt-1.5">ID: {{ $user->employee_id }}</span>
                </div>
            </div>

            <!-- Organization Section -->
            <div class="flex-1 lg:px-8 border-b lg:border-b-0 lg:border-r border-hairline/60 pb-6 lg:pb-0 flex flex-col justify-center">
                <div class="space-y-4">
                    <h3 class="text-vellum-faint text-[9.5px] font-semibold uppercase tracking-widest">Organization</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6">
                        <div>
                            <span class="text-vellum-faint text-[9.5px] uppercase tracking-wider block font-bold">Department</span>
                            <span class="font-display font-bold text-[16px] block mt-1.5 text-brass tracking-wide leading-none">{{ $user->department?->name ?? 'Not Assigned' }}</span>
                        </div>
                        <div>
                            <span class="text-vellum-faint text-[9.5px] uppercase tracking-wider block">Manager</span>
                            <span class="text-vellum font-medium text-[12.5px] block mt-1.5 leading-relaxed">{{ $user->manager?->name ?? 'Not Assigned' }}</span>
                        </div>
                        <div>
                            <span class="text-vellum-faint text-[9.5px] uppercase tracking-wider block">Assigned Admin</span>
                            <span class="text-vellum font-medium text-[12.5px] block mt-1.5 leading-relaxed">{{ $user->admin?->name ?? 'Not Assigned' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact & Employment Section -->
            <div class="flex-1 lg:pl-8 flex flex-col justify-center">
                <div class="space-y-4">
                    <h3 class="text-vellum-faint text-[9.5px] font-semibold uppercase tracking-widest">Contact & Employment</h3>
                    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 xl:gap-6">
                        <div>
                            <span class="text-vellum-faint text-[9.5px] uppercase tracking-wider block">Email Address</span>
                            <span class="text-vellum font-medium text-[12.5px] block mt-1.5 select-all break-all whitespace-normal leading-relaxed" title="Click to copy">{{ $user->email }}</span>
                        </div>
                        <div>
                            <span class="text-vellum-faint text-[9.5px] uppercase tracking-wider block">Phone Number</span>
                            <span class="text-vellum font-mono text-[12.5px] block mt-1.5 select-all leading-relaxed" title="Click to copy">{{ $user->phone ?? 'Not Provided' }}</span>
                        </div>
                        <div>
                            <span class="text-vellum-faint text-[9.5px] uppercase tracking-wider block">Joining Date</span>
                            <span class="text-vellum font-mono text-[12.5px] block mt-1.5 leading-relaxed">{{ $user->joining_date?->format('M d, Y') ?? 'Not Provided' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Calendar component (Full Width) -->
    <div class="w-full">
        <x-attendance-calendar :user="$user" />
    </div>


</x-executive-layout>
