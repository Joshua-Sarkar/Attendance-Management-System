<x-workflow-layout>
    <x-slot name="header">
        <h1 class="font-display font-medium text-[32px] tracking-wide text-vellum">{{ __('Employee Import Console') }}</h1>
        <div class="text-[13px] text-vellum-muted mt-1.5 tracking-wide">
            Automate organizational directory provisioning or run selective updates on employee profiles.
        </div>
    </x-slot>

    <div class="w-full space-y-8" x-data="{ mode: 'create' }">
        <!-- Error & Success Notifications -->
        @if ($errors->any())
            <div class="bg-burgundy-bg border border-burgundy/30 text-burgundy px-4 py-3 rounded shadow-sm">
                <h4 class="text-xs font-semibold uppercase tracking-wider mb-2 font-display">System Integrity Notification</h4>
                <ul class="list-disc pl-5 text-xs font-mono">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="bg-forest-bg border border-forest/30 text-forest px-4 py-3 rounded shadow-sm font-mono text-sm font-semibold">
                {{ session('success') }}
            </div>
        @endif

        <!-- STEP 1: Upload / Configure (Only visible when not previewing) -->
        @if (!session('import_preview'))
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Config Card -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="border border-hairline bg-surface-raised p-6 rounded shadow-sm space-y-6">
                        <h3 class="text-lg font-semibold text-brass font-display">1. Import Parameters</h3>
                        
                        <form action="{{ route('admin.import.handle') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                            @csrf

                            <!-- Import Source Selector -->
                            <div class="space-y-2">
                                <label for="profile_id" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider">Import Source</label>
                                <select name="profile_id" id="profile_id" required
                                        class="w-full text-sm text-vellum border border-hairline rounded bg-surface p-2.5 focus:outline-none focus:border-brass">
                                    <option value="auto" selected>Auto Detect Source System</option>
                                    @foreach ($profiles as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                                    @endforeach
                                </select>
                                <p class="text-[11px] text-vellum-muted leading-relaxed">
                                    Auto-detect scans the sheet's column headings and resolves the formatting layout automatically.
                                </p>
                            </div>

                            <!-- Mode Selection -->
                            <div class="space-y-3">
                                <label class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider">Import Mode</label>
                                <div class="flex items-center gap-8">
                                    <label class="inline-flex items-center text-sm text-vellum cursor-pointer">
                                        <input type="radio" name="mode" value="create" x-model="mode" checked class="text-brass border-hairline focus:ring-brass bg-surface">
                                        <span class="ml-2 font-medium">Create New Employees</span>
                                    </label>
                                    <label class="inline-flex items-center text-sm text-vellum cursor-pointer">
                                        <input type="radio" name="mode" value="update" x-model="mode" class="text-brass border-hairline focus:ring-brass bg-surface">
                                        <span class="ml-2 font-medium">Update Existing Employees</span>
                                    </label>
                                </div>
                                <p class="text-[11px] text-vellum-muted leading-relaxed" x-show="mode === 'create'">
                                    Adds new employee records matched by Official Email. Will update existing core profiles if found.
                                </p>
                                <p class="text-[11px] text-vellum-muted leading-relaxed" x-show="mode === 'update'">
                                    Modifies records in place. Unchecked categories or blank columns in the spreadsheet are strictly bypassed.
                                </p>
                            </div>

                            <!-- Selective Categories (Only for Update mode) -->
                            <div x-show="mode === 'update'" x-transition class="bg-surface p-4 rounded border border-hairline space-y-3">
                                <label class="block text-xs font-semibold text-brass uppercase tracking-wider">Selective Update Categories</label>
                                <div class="flex flex-col sm:flex-row gap-6">
                                    <label class="inline-flex items-center text-sm text-vellum cursor-pointer">
                                        <input type="checkbox" name="update_categories[]" value="base_salary" checked class="rounded text-brass border-hairline focus:ring-brass bg-surface">
                                        <span class="ml-2 font-medium">Base Salary & Payroll settings</span>
                                    </label>
                                    <label class="inline-flex items-center text-sm text-vellum cursor-pointer">
                                        <input type="checkbox" name="update_categories[]" value="leave_balances" checked class="rounded text-brass border-hairline focus:ring-brass bg-surface">
                                        <span class="ml-2 font-medium">Leave Balances</span>
                                    </label>
                                </div>
                                <p class="text-[11px] text-vellum-muted leading-relaxed">
                                    Leave balances update will automatically reconcile adjustments in the double-entry ledger.
                                </p>
                            </div>

                            <!-- File Selection -->
                            <div class="space-y-2">
                                <label for="file" class="block text-xs font-semibold text-vellum-muted uppercase tracking-wider">Spreadsheet File (Max 5MB)</label>
                                <input type="file" name="file" id="file" required
                                       class="w-full text-sm text-vellum file:mr-4 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-brass/10 file:text-brass hover:file:bg-brass/20 border border-hairline rounded p-2.5 bg-surface focus:outline-none">
                            </div>

                            <x-primary-button type="submit" class="w-full flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                </svg>
                                Analyze & Verify Spreadsheet
                            </x-primary-button>
                        </form>
                    </div>
                </div>

                <!-- Info Help Card -->
                <div class="border border-hairline bg-surface-raised p-6 rounded shadow-sm space-y-4">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-brass font-display">Data Mapping Guidelines</h3>
                    <div class="space-y-3 text-xs text-vellum-muted leading-relaxed">
                        <p>
                            The import console reads employee columns from spreadsheets, matching them dynamically based on database-driven alias profiles.
                        </p>
                        <p class="font-mono text-[11px] bg-surface p-2 rounded border border-hairline text-brass">
                            - Standard Payroll Profile matches columns such as 'Employee Code', 'Salary', 'Remaining Leave'.
                        </p>
                        <p>
                            Matches are checked against external mappings. New identifiers require manual approval to prevent accidental payroll allocation.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- STEP 2: Import Preview & Conflict Reconciliation -->
        @if (session('import_preview'))
            @php $preview = session('import_preview'); @endphp
            <div class="space-y-8">
                <!-- Header Card -->
                <div class="border border-brass/30 bg-surface-raised p-6 rounded shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <span class="text-[10px] uppercase tracking-wider font-mono font-bold px-2 py-0.5 rounded bg-brass/10 text-brass">
                            Step 2: Review Changes
                        </span>
                        <h2 class="text-xl font-semibold text-vellum font-display mt-1">
                            Reviewing: <span class="font-mono text-brass">{{ $preview['original_filename'] }}</span>
                        </h2>
                        <div class="text-xs text-vellum-muted mt-0.5">
                            Source System: <span class="font-semibold text-vellum">{{ $preview['profile']['name'] }} (v{{ $preview['profile']['version'] }})</span> ·
                            Mode: <span class="font-bold text-brass uppercase">{{ $preview['mode'] === 'create' ? 'Create Mode' : 'Selective Update Mode' }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('admin.import.show', ['cancel_preview' => 1]) }}" 
                           class="px-4 py-2 border border-hairline bg-surface text-vellum hover:bg-surface-raised rounded text-xs font-semibold uppercase tracking-wider transition duration-150">
                            Cancel & Discard
                        </a>
                    </div>
                </div>

                <!-- Auto Detect Summary Info (Requirement 3) -->
                @if ($preview['auto_detect_summary'] ?? null)
                    @php $detect = $preview['auto_detect_summary']; @endphp
                    <div class="border border-hairline rounded bg-surface-raised p-6 space-y-4">
                        <h3 class="text-sm font-semibold text-brass uppercase tracking-wider font-display">Auto-Detected Source Details</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="bg-surface p-4 rounded border border-hairline">
                                <span class="text-[10px] text-vellum-muted uppercase font-semibold">Detected Source</span>
                                <div class="text-sm font-bold text-vellum mt-1">{{ $detect['name'] }} (v{{ $detect['version'] }})</div>
                            </div>
                            <div class="bg-surface p-4 rounded border border-hairline">
                                <span class="text-[10px] text-vellum-muted uppercase font-semibold">Detection Confidence</span>
                                <div class="text-sm font-bold text-forest mt-1 font-mono">{{ $detect['confidence'] }}% Match</div>
                            </div>
                            <div class="bg-surface p-4 rounded border border-hairline">
                                <span class="text-[10px] text-vellum-muted uppercase font-semibold">Match Efficiency</span>
                                <div class="text-xs font-semibold text-forest flex items-center gap-1.5 mt-1 font-mono">
                                    {{ count($detect['matched_headers']) }} Column(s) Matched
                                </div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-2">
                            <div class="space-y-1.5">
                                <span class="text-[9px] text-vellum-muted uppercase font-semibold font-mono block">Matched Headers</span>
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($detect['matched_headers'] as $h)
                                        <span class="px-1.5 py-0.5 bg-forest-bg text-forest rounded text-[9.5px] font-mono border border-forest/10">{{ $h }}</span>
                                    @endforeach
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <span class="text-[9px] text-vellum-muted uppercase font-semibold font-mono block">Missing Expected Headers</span>
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($detect['missing_headers'] as $h)
                                        <span class="px-1.5 py-0.5 bg-burgundy-bg text-burgundy rounded text-[9.5px] font-mono border border-burgundy/10">{{ $h }}</span>
                                    @empty
                                        <span class="text-[10px] text-vellum-faint font-mono">None</span>
                                    @endforelse
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <span class="text-[9px] text-vellum-muted uppercase font-semibold font-mono block">Unrecognized Columns</span>
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($detect['unknown_headers'] as $h)
                                        <span class="px-1.5 py-0.5 bg-surface text-vellum-muted rounded text-[9.5px] font-mono border border-hairline">{{ $h }}</span>
                                    @empty
                                        <span class="text-[10px] text-vellum-faint font-mono">None</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Spreadsheet Health Section (Expandable sections - Requirement 4) -->
                @php $health = $preview['spreadsheet_health']; @endphp
                <div class="border border-hairline rounded bg-surface-raised p-6 space-y-4">
                    <h3 class="text-sm font-semibold text-brass uppercase tracking-wider font-display">Spreadsheet Health Audit</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- 1. Required Headers -->
                        <div class="border border-hairline rounded bg-surface p-4">
                            <details class="group" {{ count($health['missing_required_columns']) > 0 ? 'open' : '' }}>
                                <summary class="flex justify-between items-center font-semibold text-xs uppercase tracking-wider text-brass cursor-pointer select-none">
                                    <span>Required Headers ({{ count($health['missing_required_columns']) }})</span>
                                    <span class="text-vellum-muted group-open:rotate-180 transition-transform duration-200">▼</span>
                                </summary>
                                <div class="mt-3 text-xs text-vellum-muted space-y-1 pl-2 border-l border-brass/30">
                                    @forelse ($health['missing_required_columns'] as $col)
                                        <div class="text-burgundy font-mono">Missing required column: '{{ $col }}'</div>
                                    @empty
                                        <div class="text-forest font-mono">All required headers are present.</div>
                                    @endforelse
                                </div>
                            </details>
                        </div>

                        <!-- 2. Unknown Headers -->
                        <div class="border border-hairline rounded bg-surface p-4">
                            <details class="group">
                                <summary class="flex justify-between items-center font-semibold text-xs uppercase tracking-wider text-brass cursor-pointer select-none">
                                    <span>Unknown Headers ({{ count($health['unknown_columns']) }})</span>
                                    <span class="text-vellum-muted group-open:rotate-180 transition-transform duration-200">▼</span>
                                </summary>
                                <div class="mt-3 text-xs text-vellum-muted pl-2 border-l border-brass/30">
                                    <div class="flex flex-wrap gap-1">
                                        @forelse ($health['unknown_columns'] as $col)
                                            <span class="px-1.5 py-0.5 bg-surface rounded text-[10px] font-mono text-vellum-muted border border-hairline">{{ $col }}</span>
                                        @empty
                                            <div class="text-forest font-mono">No unknown headers detected.</div>
                                        @endforelse
                                    </div>
                                </div>
                            </details>
                        </div>

                        <!-- 3. Duplicate Employee Identifiers -->
                        <div class="border border-hairline rounded bg-surface p-4">
                            <details class="group" {{ count($health['duplicate_employee_identifiers']) > 0 ? 'open' : '' }}>
                                <summary class="flex justify-between items-center font-semibold text-xs uppercase tracking-wider text-brass cursor-pointer select-none">
                                    <span>Duplicate Employee Identifiers ({{ count($health['duplicate_employee_identifiers']) }})</span>
                                    <span class="text-vellum-muted group-open:rotate-180 transition-transform duration-200">▼</span>
                                </summary>
                                <div class="mt-3 text-xs text-vellum-muted space-y-1 pl-2 border-l border-brass/30 max-h-40 overflow-y-auto">
                                    @forelse ($health['duplicate_employee_identifiers'] as $dup)
                                        <div class="text-burgundy font-mono">Row {{ $dup['row'] }}: Code '{{ $dup['value'] }}' duplicates value at Row {{ $dup['original_row'] }}</div>
                                    @empty
                                        <div class="text-forest font-mono">No duplicate employee identifiers found.</div>
                                    @endforelse
                                </div>
                            </details>
                        </div>

                        <!-- 4. Duplicate Emails -->
                        <div class="border border-hairline rounded bg-surface p-4">
                            <details class="group" {{ count($health['duplicate_emails']) > 0 ? 'open' : '' }}>
                                <summary class="flex justify-between items-center font-semibold text-xs uppercase tracking-wider text-brass cursor-pointer select-none">
                                    <span>Duplicate Email Addresses ({{ count($health['duplicate_emails']) }})</span>
                                    <span class="text-vellum-muted group-open:rotate-180 transition-transform duration-200">▼</span>
                                </summary>
                                <div class="mt-3 text-xs text-vellum-muted space-y-1 pl-2 border-l border-brass/30 max-h-40 overflow-y-auto">
                                    @forelse ($health['duplicate_emails'] as $dup)
                                        <div class="text-burgundy font-mono">Row {{ $dup['row'] }}: Email '{{ $dup['value'] }}' duplicates value at Row {{ $dup['original_row'] }}</div>
                                    @empty
                                        <div class="text-forest font-mono">No duplicate emails found.</div>
                                    @endforelse
                                </div>
                            </details>
                        </div>

                        <!-- 5. Duplicate Names -->
                        <div class="border border-hairline rounded bg-surface p-4">
                            <details class="group" {{ count($health['duplicate_names']) > 0 ? 'open' : '' }}>
                                <summary class="flex justify-between items-center font-semibold text-xs uppercase tracking-wider text-brass cursor-pointer select-none">
                                    <span>Duplicate Names within Sheet ({{ count($health['duplicate_names']) }})</span>
                                    <span class="text-vellum-muted group-open:rotate-180 transition-transform duration-200">▼</span>
                                </summary>
                                <div class="mt-3 text-xs text-vellum-muted space-y-1 pl-2 border-l border-brass/30 max-h-40 overflow-y-auto">
                                    @forelse ($health['duplicate_names'] as $dup)
                                        <div class="text-brass font-mono">Row {{ $dup['row'] }}: Name '{{ $dup['value'] }}' duplicates value at Row {{ $dup['original_row'] }}</div>
                                    @empty
                                        <div class="text-forest font-mono">No duplicate names found.</div>
                                    @endforelse
                                </div>
                            </details>
                        </div>

                        <!-- 6. Invalid Salary Values -->
                        <div class="border border-hairline rounded bg-surface p-4">
                            <details class="group" {{ count($health['invalid_salary_values']) > 0 ? 'open' : '' }}>
                                <summary class="flex justify-between items-center font-semibold text-xs uppercase tracking-wider text-brass cursor-pointer select-none">
                                    <span>Invalid Salary Values ({{ count($health['invalid_salary_values']) }})</span>
                                    <span class="text-vellum-muted group-open:rotate-180 transition-transform duration-200">▼</span>
                                </summary>
                                <div class="mt-3 text-xs text-vellum-muted space-y-1 pl-2 border-l border-brass/30 max-h-40 overflow-y-auto">
                                    @forelse ($health['invalid_salary_values'] as $err)
                                        <div class="text-burgundy font-mono">Row {{ $err['row'] }}: Value '{{ $err['value'] }}' is invalid (must be positive number).</div>
                                    @empty
                                        <div class="text-forest font-mono">All salary values are valid.</div>
                                    @endforelse
                                </div>
                            </details>
                        </div>

                        <!-- 7. Invalid Leave Values -->
                        <div class="border border-hairline rounded bg-surface p-4">
                            <details class="group" {{ count($health['invalid_leave_values']) > 0 ? 'open' : '' }}>
                                <summary class="flex justify-between items-center font-semibold text-xs uppercase tracking-wider text-brass cursor-pointer select-none">
                                    <span>Invalid Leave Values ({{ count($health['invalid_leave_values']) }})</span>
                                    <span class="text-vellum-muted group-open:rotate-180 transition-transform duration-200">▼</span>
                                </summary>
                                <div class="mt-3 text-xs text-vellum-muted space-y-1 pl-2 border-l border-brass/30 max-h-40 overflow-y-auto">
                                    @forelse ($health['invalid_leave_values'] as $err)
                                        <div class="text-burgundy font-mono">Row {{ $err['row'] }}: Field '{{ $err['field'] }}' has invalid value '{{ $err['value'] }}'.</div>
                                    @empty
                                        <div class="text-forest font-mono">All leave values are valid.</div>
                                    @endforelse
                                </div>
                            </details>
                        </div>

                        <!-- 8. Blank Required Cells -->
                        <div class="border border-hairline rounded bg-surface p-4">
                            <details class="group" {{ count($health['blank_required_cells']) > 0 ? 'open' : '' }}>
                                <summary class="flex justify-between items-center font-semibold text-xs uppercase tracking-wider text-brass cursor-pointer select-none">
                                    <span>Blank Required Cells ({{ count($health['blank_required_cells']) }})</span>
                                    <span class="text-vellum-muted group-open:rotate-180 transition-transform duration-200">▼</span>
                                </summary>
                                <div class="mt-3 text-xs text-vellum-muted space-y-1 pl-2 border-l border-brass/30 max-h-40 overflow-y-auto">
                                    @forelse ($health['blank_required_cells'] as $err)
                                        <div class="text-burgundy font-mono">Row {{ $err['row'] }}: Required field '{{ $err['field'] }}' is blank.</div>
                                    @empty
                                        <div class="text-forest font-mono">No blank required cells found.</div>
                                    @endforelse
                                </div>
                            </details>
                        </div>

                        <!-- 9. Invalid Dates -->
                        <div class="border border-hairline rounded bg-surface p-4">
                            <details class="group" {{ count($health['invalid_dates']) > 0 ? 'open' : '' }}>
                                <summary class="flex justify-between items-center font-semibold text-xs uppercase tracking-wider text-brass cursor-pointer select-none">
                                    <span>Invalid Dates ({{ count($health['invalid_dates']) }})</span>
                                    <span class="text-vellum-muted group-open:rotate-180 transition-transform duration-200">▼</span>
                                </summary>
                                <div class="mt-3 text-xs text-vellum-muted space-y-1 pl-2 border-l border-brass/30 max-h-40 overflow-y-auto">
                                    @forelse ($health['invalid_dates'] as $err)
                                        <div class="text-burgundy font-mono">Row {{ $err['row'] }}: Date field '{{ $err['field'] }}' has invalid format '{{ $err['value'] }}'.</div>
                                    @empty
                                        <div class="text-forest font-mono">All date values are valid.</div>
                                    @endforelse
                                </div>
                            </details>
                        </div>
                    </div>
                </div>

                <!-- Preview Statistics Summary Grid -->
                <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
                    <div class="bg-surface-raised p-4 rounded border border-hairline text-center">
                        <span class="text-[10px] text-vellum-faint uppercase font-semibold tracking-wider">Matched</span>
                        <h4 class="text-2xl font-bold text-vellum mt-1 font-mono">{{ $preview['matched_count'] }}</h4>
                    </div>
                    <div class="bg-surface-raised p-4 rounded border border-hairline text-center">
                        <span class="text-[10px] text-vellum-faint uppercase font-semibold tracking-wider">Expected Updates</span>
                        <h4 class="text-2xl font-bold text-forest mt-1 font-mono">{{ $preview['updated_count'] }}</h4>
                    </div>
                    <div class="bg-surface-raised p-4 rounded border border-hairline text-center">
                        <span class="text-[10px] text-vellum-faint uppercase font-semibold tracking-wider">Skipped Rows</span>
                        <h4 class="text-2xl font-bold text-brass mt-1 font-mono">{{ $preview['skipped_count'] }}</h4>
                    </div>
                    <div class="bg-surface-raised p-4 rounded border border-hairline text-center">
                        <span class="text-[10px] text-vellum-faint uppercase font-semibold tracking-wider">Verification Required</span>
                        <h4 class="text-2xl font-bold text-brass mt-1 font-mono">{{ $preview['needs_manual_review_count'] }}</h4>
                    </div>
                    <div class="bg-surface-raised p-4 rounded border border-hairline text-center">
                        <span class="text-[10px] text-vellum-faint uppercase font-semibold tracking-wider">Recommended Matches</span>
                        <h4 class="text-2xl font-bold text-forest mt-1 font-mono">{{ $preview['suggested_matches_count'] }}</h4>
                    </div>
                    <div class="bg-surface-raised p-4 rounded border border-hairline text-center">
                        <span class="text-[10px] text-vellum-faint uppercase font-semibold tracking-wider">Not Found / Errors</span>
                        <h4 class="text-2xl font-bold text-burgundy mt-1 font-mono">{{ $preview['not_found_count'] + count($preview['validation_errors']) }}</h4>
                    </div>
                </div>

                <!-- Forms containing Suggested Matches and Autocomplete Manual Reviews -->
                <!-- Forms containing Suggested Matches and Autocomplete Manual Reviews -->
                <form action="{{ count($preview['needs_manual_review']) > 0 ? route('admin.import.handle') : route('admin.import.confirm') }}" method="POST" class="space-y-8">
                    @csrf
                    <input type="hidden" name="temp_file_path" value="{{ $preview['temp_file_path'] }}">
                    <input type="hidden" name="mode" value="{{ $preview['mode'] }}">
                    @foreach ($preview['update_categories'] as $category)
                        <input type="hidden" name="update_categories[]" value="{{ $category }}">
                    @endforeach
                    <input type="hidden" name="original_filename" value="{{ $preview['original_filename'] }}">
                    <input type="hidden" name="profile_id" value="{{ $preview['profile']['id'] }}">

                    <!-- Retain already resolved mappings and create mappings between steps -->
                    <input type="hidden" name="create_mappings[_ui]" value="1">
                    @if (isset($preview['approved_mappings']))
                        @foreach ($preview['approved_mappings'] as $extCode => $targetUserId)
                            <input type="hidden" name="approved_mappings[{{ $extCode }}]" value="{{ $targetUserId }}">
                        @endforeach
                    @endif
                    @if (isset($preview['create_mappings']))
                        @foreach ($preview['create_mappings'] as $extCode => $val)
                            @if ($extCode !== '_ui')
                                <input type="hidden" name="create_mappings[{{ $extCode }}]" value="{{ $val }}">
                            @endif
                        @endforeach
                    @endif

                    <!-- Card Section: Employee Verification Required (Autocomplete & Scored Candidates - Requirement 2) -->
                    @if (count($preview['needs_manual_review']) > 0)
                        <div class="border border-hairline rounded bg-surface-raised p-6 space-y-6">
                            <div class="border-b border-hairline pb-2.5">
                                <h3 class="text-sm font-semibold text-brass uppercase tracking-wider font-display">Employee Verification Required</h3>
                                <p class="text-xs text-vellum-muted mt-1 leading-relaxed">
                                    The following spreadsheet entries could not be matched automatically. Please resolve each row before continuing.
                                </p>
                            </div>

                            <div class="space-y-6">
                                @foreach ($preview['needs_manual_review'] as $reviewIndex => $row)
                                    <div x-data="{
                                        search: '{{ $row['candidates'][0]['name'] ?? '' }}',
                                        selectedId: '{{ $row['candidates'][0]['id'] ?? '' }}',
                                        selectedName: '{{ $row['candidates'][0]['name'] ?? '' }}',
                                        selectedEmployeeId: '{{ $row['candidates'][0]['employee_id'] ?? '' }}',
                                        suggestions: [],
                                        open: false,
                                        saveMapping: true,
                                        fetchSuggestions() {
                                            if (this.search.length < 2) {
                                                this.suggestions = [];
                                                return;
                                            }
                                            fetch('/admin/employees/search?q=' + encodeURIComponent(this.search))
                                                .then(res => res.json())
                                                .then(data => {
                                                    this.suggestions = data;
                                                });
                                        },
                                        selectUser(user) {
                                            this.selectedId = user.id;
                                            this.selectedName = user.name;
                                            this.selectedEmployeeId = user.employee_id;
                                            this.search = user.name + ' (' + user.employee_id + ')';
                                            this.open = false;
                                        },
                                        skipRow() {
                                            this.selectedId = 'skip';
                                            this.selectedName = '';
                                            this.selectedEmployeeId = '';
                                            this.search = '';
                                        }
                                    }" class="border border-hairline bg-surface p-5 rounded space-y-4 shadow-sm">
                                        
                                        <!-- Header line with row number and diagnostics -->
                                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 border-b border-hairline pb-2.5">
                                            <div>
                                                <span class="text-[10px] uppercase font-mono tracking-wider font-bold text-brass">Row {{ $row['row'] }}</span>
                                                <h4 class="text-sm font-semibold text-vellum mt-0.5">Spreadsheet Name: <span class="font-mono text-brass">{{ $row['name'] }}</span></h4>
                                            </div>
                                            <div class="flex flex-wrap gap-1">
                                                @foreach ($row['diagnostics'] ?? [] as $diagnostic)
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-mono font-medium {{ str_contains($diagnostic, 'matched') || str_contains($diagnostic, 'One') || str_contains($diagnostic, 'possible') ? 'bg-forest-bg text-forest border border-forest/25' : (str_contains($diagnostic, 'required') ? 'bg-brass/10 text-brass border border-brass/25' : 'bg-burgundy-bg text-burgundy border border-burgundy/25') }}">
                                                        {{ $diagnostic }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 text-xs">
                                            <!-- Col 1: Spreadsheet values -->
                                            <div class="space-y-3">
                                                <div>
                                                    <span class="text-[10px] uppercase tracking-wider text-brass font-bold block mb-1">Spreadsheet Values</span>
                                                    <table class="w-full font-mono text-[11px] divide-y divide-hairline">
                                                        <tbody>
                                                            <tr>
                                                                <td class="py-1 text-vellum-muted">Employee ID:</td>
                                                                <td class="py-1 text-vellum text-right font-bold">{{ $row['external_code'] }}</td>
                                                            </tr>
                                                            <tr>
                                                                <td class="py-1 text-vellum-muted">Email:</td>
                                                                <td class="py-1 text-vellum text-right">{{ $row['email'] }}</td>
                                                            </tr>
                                                            <tr>
                                                                <td class="py-1 text-vellum-muted">Department:</td>
                                                                <td class="py-1 text-vellum text-right">{{ $row['department'] }}</td>
                                                            </tr>
                                                            <tr>
                                                                <td class="py-1 text-vellum-muted">Designation:</td>
                                                                <td class="py-1 text-vellum text-right">{{ $row['designation'] }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>

                                            <!-- Col 2: Suggested Candidates ranked by score -->
                                            <div class="space-y-3">
                                                <span class="text-[10px] uppercase tracking-wider text-brass font-bold block">Suggested Candidates</span>
                                                <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                                                    @forelse ($row['candidates'] as $cand)
                                                        <div class="bg-surface-raised p-2.5 rounded border border-hairline space-y-1.5 transition hover:border-brass/40">
                                                            <div class="flex justify-between items-start">
                                                                <div>
                                                                    <span class="font-semibold text-vellum">{{ $cand['name'] }}</span>
                                                                    <span class="text-[10px] font-mono text-vellum-muted block">{{ $cand['employee_id'] }} · {{ $cand['department'] }} · {{ $cand['designation'] }}</span>
                                                                </div>
                                                                <span class="text-[10px] font-mono text-vellum-muted">{{ $cand['email'] }}</span>
                                                            </div>
                                                            
                                                            <!-- Matched fields bullet list -->
                                                            <div class="border-t border-hairline/50 pt-1 mt-1">
                                                                <span class="text-[9px] uppercase tracking-wider text-vellum-muted block mb-0.5">Matched Fields:</span>
                                                                <div class="flex flex-wrap gap-x-2 gap-y-0.5">
                                                                    @foreach ($cand['matched_fields'] as $field => $isMatched)
                                                                        <span class="font-mono text-[9px] {{ $isMatched ? 'text-forest font-bold' : 'text-vellum-muted' }}">
                                                                            {!! $isMatched ? '✓' : '✗' !!} {{ $field }}
                                                                        </span>
                                                                    @endforeach
                                                                </div>
                                                            </div>

                                                            <div class="text-right pt-1.5">
                                                                <button type="button" 
                                                                        @click="selectedId = '{{ $cand['id'] }}'; selectedName = '{{ $cand['name'] }}'; selectedEmployeeId = '{{ $cand['employee_id'] }}'; search = '{{ $cand['name'] }} ({{ $cand['employee_id'] }})'"
                                                                        class="px-2 py-0.5 bg-brass/10 hover:bg-brass text-brass hover:text-canvas text-[9.5px] uppercase tracking-wider font-semibold rounded transition duration-150">
                                                                    Assign to Existing Employee
                                                                </button>
                                                            </div>
                                                        </div>
                                                    @empty
                                                        <div class="text-[10px] text-vellum-faint text-center py-4">No candidates resolved.</div>
                                                    @endforelse
                                                </div>
                                            </div>

                                            <!-- Col 3: Search and override actions -->
                                            <div class="space-y-4 flex flex-col justify-between">
                                                <div class="space-y-2">
                                                    <span class="text-[10px] uppercase tracking-wider text-brass font-bold block font-sans">Search Employee</span>
                                                    <div class="relative w-full">
                                                        <input type="text" 
                                                               x-model="search" 
                                                               @input.debounce.300ms="fetchSuggestions()" 
                                                               @focus="open = true" 
                                                               class="w-full text-xs border border-hairline rounded bg-surface p-2 text-vellum focus:outline-none focus:border-brass"
                                                               placeholder="Search by name, code, or email...">
                                                        <input type="hidden" :name="'approved_mappings[' + '{{ $row['external_code'] }}' + ']'" :value="selectedId">
                                                        
                                                        <!-- Search Popup -->
                                                        <div x-show="open && suggestions.length > 0" 
                                                             @click.away="open = false" 
                                                             class="absolute z-10 w-full mt-1 border border-hairline bg-surface-raised rounded shadow-lg max-h-48 overflow-y-auto divide-y divide-hairline">
                                                            <template x-for="user in suggestions" :key="user.id">
                                                                <div @click="selectUser(user)" class="p-2 text-xs hover:bg-brass/10 cursor-pointer text-vellum">
                                                                    <span x-text="user.name" class="font-semibold"></span>
                                                                    <span x-text="' (' + user.employee_id + ')'" class="text-vellum-muted font-mono"></span>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Create external mapping and resolution status check -->
                                                <div class="space-y-3 pt-2">
                                                    <div class="flex items-center gap-2">
                                                        <label class="inline-flex items-center text-[10.5px] text-vellum cursor-pointer">
                                                            <input type="checkbox" :name="'create_mappings[' + '{{ $row['external_code'] }}' + ']'" value="1" x-model="saveMapping"
                                                                   class="rounded text-brass border-hairline focus:ring-brass bg-surface">
                                                            <span class="ml-2 font-semibold uppercase tracking-wider text-brass">Create External Identifier Mapping</span>
                                                        </label>
                                                    </div>

                                                    <div class="p-2.5 rounded bg-surface border border-hairline">
                                                        <div class="text-[9px] uppercase tracking-wider text-vellum-muted font-semibold">Resolution Status</div>
                                                        <div class="mt-1 flex items-center justify-between text-[11px]">
                                                            <div x-show="selectedId && selectedId !== 'skip'" class="text-forest font-semibold flex items-center gap-1.5 font-mono">
                                                                <span class="w-1.5 h-1.5 rounded-full bg-forest"></span>
                                                                <span>ASSIGNED: <span x-text="selectedEmployeeId" class="text-brass"></span></span>
                                                            </div>
                                                            <div x-show="!selectedId || selectedId === 'skip'" class="text-brass font-semibold flex items-center gap-1.5 font-mono">
                                                                <span class="w-1.5 h-1.5 rounded-full bg-brass"></span>
                                                                <span>SKIPPED</span>
                                                            </div>
                                                            <button type="button" @click="skipRow()" class="text-[10px] text-burgundy font-bold uppercase hover:underline">
                                                                Skip Row
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Card Section: Recommended Employee (Checkboxes) -->
                    @if (count($preview['suggested_employee_matches']) > 0)
                        <div class="border border-hairline rounded bg-surface-raised p-6 space-y-4">
                            <div class="border-b border-hairline pb-2">
                                <h3 class="text-sm font-semibold text-brass uppercase tracking-wider font-display">Recommended Employees</h3>
                                <p class="text-xs text-vellum-muted mt-1 leading-relaxed">
                                    The following employees resolved with high scores above confidence threshold. Check boxes to create database mappings.
                                </p>
                            </div>

                            <div class="space-y-4 divide-y divide-hairline">
                                @foreach ($preview['suggested_employee_matches'] as $match)
                                    <div class="pt-4 first:pt-0 grid grid-cols-1 lg:grid-cols-3 gap-6 items-center">
                                        <!-- Column 1: Code and Match Info -->
                                        <div>
                                            <span class="text-[10px] font-mono text-vellum-faint">ROW {{ $match['row'] }} · External Code:</span>
                                            <div class="text-sm font-bold font-mono text-brass">{{ $match['external_code'] }}</div>
                                            <div class="text-xs text-vellum-muted font-mono mt-0.5">{{ $match['email'] }}</div>
                                        </div>

                                        <!-- Column 2: Target User Matches -->
                                        <div class="bg-surface p-3 rounded border border-hairline">
                                            <div class="text-xs font-bold text-vellum">
                                                {{ $match['user_name'] }} ({{ $match['employee_id'] }})
                                            </div>
                                            <div class="flex items-center gap-1.5 mt-1 font-mono text-[10px] text-forest font-bold">
                                                <span>Match Score: {{ $match['confidence'] }} pts</span>
                                            </div>
                                            <div class="text-[9px] text-vellum-muted font-mono leading-tight mt-1 truncate" title="{{ $match['resolution_method'] }}">
                                                Basis: {{ $match['resolution_method'] }}
                                            </div>
                                        </div>

                                        <!-- Column 3: Association Checkbox -->
                                        <div class="flex items-center gap-3">
                                            <label class="inline-flex items-center text-xs text-vellum cursor-pointer">
                                                <input type="checkbox" name="approved_mappings[{{ $match['external_code'] }}]" value="{{ $match['user_id'] }}" checked
                                                       class="rounded text-brass border-hairline focus:ring-brass bg-surface">
                                                <span class="ml-2 font-semibold uppercase tracking-wider font-sans text-brass text-[10.5px]">Create External Identifier Mapping</span>
                                            </label>
                                            <input type="hidden" name="create_mappings[{{ $match['external_code'] }}]" value="1">
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Previews & Errors Lists -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <h4 class="text-xs font-semibold text-vellum-muted uppercase tracking-wider">Fields to be Updated</h4>
                            <div class="flex flex-wrap gap-1.5">
                                @forelse ($preview['fields_to_update'] as $field)
                                    <span class="px-2 py-1 bg-forest-bg text-forest border border-forest/10 rounded text-[11px] font-medium">{{ $field }}</span>
                                @empty
                                    <span class="text-xs text-vellum-faint font-mono">No fields will be modified</span>
                                @endforelse
                            </div>
                        </div>
                        <div class="space-y-2">
                            <h4 class="text-xs font-semibold text-vellum-muted uppercase tracking-wider">Fields Being Ignored / Untouched</h4>
                            <div class="flex flex-wrap gap-1.5">
                                @forelse ($preview['fields_ignored'] as $field)
                                    <span class="px-2 py-1 bg-surface text-vellum-muted border border-hairline rounded text-[11px] font-mono">{{ $field }}</span>
                                @empty
                                    <span class="text-xs text-vellum-faint font-mono">All fields will be updated</span>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Row Validation Errors list -->
                    @if (count($preview['validation_errors']) > 0)
                        <div class="space-y-2">
                            <h4 class="text-xs font-semibold text-burgundy uppercase tracking-wider">Employee Verification Required Issues</h4>
                            <div class="overflow-x-auto max-h-48 border border-hairline rounded bg-surface">
                                <table class="w-full text-xs text-left">
                                    <thead class="bg-surface-raised border-b border-hairline sticky top-0">
                                        <tr class="text-[10px] text-vellum-muted uppercase font-semibold">
                                            <th class="py-2 px-3 w-16">Row</th>
                                            <th class="py-2 px-3 w-32">Employee Code</th>
                                            <th class="py-2 px-3">Reason / Details</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-hairline font-mono text-[11px]">
                                        @foreach ($preview['validation_errors'] as $error)
                                            <tr class="hover:bg-burgundy/[0.02]">
                                                <td class="py-2 px-3 text-vellum-muted">{{ $error['row'] }}</td>
                                                <td class="py-2 px-3 text-brass font-bold">{{ $error['employee_code'] ?? 'N/A' }}</td>
                                                <td class="py-2 px-3 text-burgundy font-semibold">{{ $error['reason'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <!-- Submit Actions -->
                    <div class="flex flex-col sm:flex-row gap-4 pt-4 border-t border-hairline">
                        @if (count($preview['needs_manual_review']) > 0)
                            <!-- Need to submit back to handle route to verify again -->
                            <input type="hidden" name="temp_file_path" value="{{ $preview['temp_file_path'] }}">
                            <input type="hidden" name="original_filename" value="{{ $preview['original_filename'] }}">
                            <x-primary-button type="submit" class="flex-1 justify-center !bg-brass hover:!bg-brass/90 !text-canvas !h-[42px] font-semibold text-sm">
                                Verify and Proceed to Preview
                            </x-primary-button>
                        @else
                            <x-primary-button type="submit" class="flex-1 justify-center !bg-forest hover:!bg-forest/90 !text-canvas !h-[42px] font-semibold text-sm">
                                Confirm and Apply Import
                            </x-primary-button>
                        @endif
                        <a href="{{ route('admin.import.show', ['cancel_preview' => 1]) }}" 
                           class="flex-1 inline-flex items-center justify-center px-4 py-2 border border-hairline bg-surface text-vellum hover:bg-surface-raised rounded text-xs font-semibold uppercase tracking-wider transition duration-150 h-[42px]">
                            Discard Changes & Delete Temp File
                        </a>
                    </div>
                </form>
            </div>
        @endif

        <!-- STEP 3: Post-Import Success Summary -->
        @if (session('import_results'))
            @php $results = session('import_results'); @endphp
            <div class="border border-hairline bg-surface-raised p-6 rounded shadow-sm space-y-6">
                <div class="flex items-center justify-between border-b border-hairline pb-3">
                    <div>
                        <span class="text-[10px] uppercase tracking-wider font-mono font-bold px-2 py-0.5 rounded bg-forest-bg text-forest">
                            Import Summary
                        </span>
                        <h2 class="text-xl font-semibold text-vellum font-display mt-1">Import Job Complete</h2>
                    </div>
                    <div class="text-right">
                        <div class="text-[10px] uppercase tracking-wider font-semibold text-vellum-muted">Processing Duration</div>
                        <div class="text-sm font-mono font-bold text-brass">{{ $results['duration_seconds'] }} seconds</div>
                    </div>
                </div>

                <!-- Stats Summary Cards -->
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
                    <div class="bg-surface p-4 rounded border border-hairline text-center">
                        <span class="text-[10px] text-vellum-faint uppercase font-semibold tracking-wider">Processed</span>
                        <h4 class="text-2xl font-bold text-vellum mt-1 font-mono">{{ $results['rows_processed'] }}</h4>
                    </div>
                    <div class="bg-surface p-4 rounded border border-hairline text-center">
                        <span class="text-[10px] text-vellum-faint uppercase font-semibold tracking-wider">Created</span>
                        <h4 class="text-2xl font-bold text-forest mt-1 font-mono">{{ $results['created'] }}</h4>
                    </div>
                    <div class="bg-surface p-4 rounded border border-hairline text-center">
                        <span class="text-[10px] text-vellum-faint uppercase font-semibold tracking-wider">Updated</span>
                        <h4 class="text-2xl font-bold text-brass mt-1 font-mono">{{ $results['updated'] }}</h4>
                    </div>
                    <div class="bg-surface p-4 rounded border border-hairline text-center">
                        <span class="text-[10px] text-vellum-faint uppercase font-semibold tracking-wider">Skipped</span>
                        <h4 class="text-2xl font-bold text-vellum mt-1 font-mono">{{ $results['skipped'] }}</h4>
                    </div>
                    <div class="bg-surface p-4 rounded border border-hairline text-center">
                        <span class="text-[10px] text-vellum-faint uppercase font-semibold tracking-wider">Failed</span>
                        <h4 class="text-2xl font-bold text-burgundy mt-1 font-mono">{{ count($results['errors']) }}</h4>
                    </div>
                </div>

                <!-- Successfully processed users table -->
                @php
                    $importedUsers = \App\Models\User::where('updated_at', '>=', now()->subMinutes(1))
                        ->orderBy('updated_at', 'desc')
                        ->get();
                @endphp

                @if ($importedUsers->count() > 0)
                    <div class="space-y-3">
                        <h4 class="text-xs font-semibold text-vellum font-display">Successfully Mapped & Updated Employees</h4>
                        <div class="overflow-x-auto border border-hairline rounded">
                            <table class="w-full text-xs text-left font-mono">
                                <thead>
                                    <tr class="bg-surface border-b border-hairline uppercase text-[10px] tracking-wider text-vellum-muted font-semibold">
                                        <th class="py-2.5 px-3 text-left">Employee ID</th>
                                        <th class="py-2.5 px-3 text-left">Name</th>
                                        <th class="py-2.5 px-3 text-left">Email</th>
                                        <th class="py-2.5 px-3 text-center">Status</th>
                                        <th class="py-2.5 px-3 text-right font-sans">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-hairline text-[11px] bg-surface">
                                    @foreach ($importedUsers as $u)
                                        <tr class="hover:bg-brass/[0.04] transition duration-150">
                                            <td class="py-2.5 px-3 text-left text-brass font-bold">{{ $u->employee_id ?? 'N/A' }}</td>
                                            <td class="py-2.5 px-3 text-left text-vellum font-sans font-semibold">{{ $u->name }}</td>
                                            <td class="py-2.5 px-3 text-left text-vellum-muted">{{ $u->email }}</td>
                                            <td class="py-2.5 px-3 text-center">
                                                <span class="px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wider rounded {{ $u->status === 'active' ? 'bg-forest-bg text-forest' : 'bg-burgundy-bg text-burgundy' }}">
                                                    {{ $u->status }}
                                                </span>
                                            </td>
                                            <td class="py-2.5 px-3 text-right">
                                                <a href="{{ route('employees.show', $u) }}"
                                                   class="inline-flex items-center px-2 py-0.5 bg-brass text-canvas rounded text-[10px] font-sans font-semibold uppercase tracking-wider hover:bg-brass/90 transition duration-150">
                                                    View Dossier
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <!-- Import History Section -->
        <div class="space-y-4">
            <h3 class="text-lg font-semibold text-brass font-display">Recent Imports (Last 10 Runs)</h3>
            
            <div class="overflow-x-auto border border-hairline rounded bg-surface-raised">
                <table class="w-full text-xs text-left">
                    <thead>
                        <tr class="bg-surface border-b border-hairline uppercase text-[10px] tracking-wider text-vellum-muted font-semibold">
                            <th class="py-2.5 px-3 text-left">Filename</th>
                            <th class="py-2.5 px-3 text-left">Imported By</th>
                            <th class="py-2.5 px-3 text-left">Date & Time</th>
                            <th class="py-2.5 px-3 text-left">Details</th>
                            <th class="py-2.5 px-3 text-right font-mono">Processed</th>
                            <th class="py-2.5 px-3 text-right font-mono">Created</th>
                            <th class="py-2.5 px-3 text-right font-mono">Updated</th>
                            <th class="py-2.5 px-3 text-right font-mono">Skipped</th>
                            <th class="py-2.5 px-3 text-center">Errors</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-hairline font-mono text-[11px] bg-surface">
                        @forelse ($history as $log)
                            <tr class="hover:bg-brass/[0.04] transition duration-150">
                                <td class="py-2.5 px-3 text-left text-vellum font-semibold font-sans truncate max-w-xs" title="{{ $log->filename }}">
                                    {{ $log->filename }}
                                </td>
                                <td class="py-2.5 px-3 text-left text-vellum-muted font-sans">
                                    {{ $log->runByUser ? $log->runByUser->name : 'System/CLI' }}
                                </td>
                                <td class="py-2.5 px-3 text-left text-vellum-muted">
                                    {{ $log->created_at->timezone('Asia/Kolkata')->format('Y-m-d h:i A') }}
                                </td>
                                <td class="py-2.5 px-3 text-left text-vellum-muted text-[10px]">
                                    @if ($log->duration_seconds !== null)
                                        <span class="font-bold">Dur:</span> {{ $log->duration_seconds }}s
                                    @endif
                                    @if (!empty($log->metadata['mode']))
                                        <span class="ml-1 text-[9px] uppercase px-1.5 py-0.2 rounded border bg-surface text-brass">{{ $log->metadata['mode'] }}</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-right font-bold text-vellum">
                                    {{ $log->rows_processed }}
                                </td>
                                <td class="py-2.5 px-3 text-right text-forest font-semibold">
                                    {{ $log->created_count }}
                                </td>
                                <td class="py-2.5 px-3 text-right text-brass font-semibold">
                                    {{ $log->updated_count }}
                                </td>
                                <td class="py-2.5 px-3 text-right text-vellum-muted">
                                    {{ $log->skipped_count }}
                                </td>
                                <td class="py-2.5 px-3 text-center font-sans">
                                    @if ($log->error_count > 0)
                                        <span class="px-2 py-0.5 text-[9.5px] font-mono font-semibold uppercase tracking-wider rounded bg-burgundy-bg text-burgundy">
                                            {{ $log->error_count }}
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 text-[9.5px] font-mono font-semibold uppercase tracking-wider rounded bg-forest-bg text-forest">
                                            0
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-6 px-3 text-center text-vellum-faint font-sans bg-surface">
                                    No employee imports have been recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-workflow-layout>
