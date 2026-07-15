@extends('layouts.ledger')

@section('title', 'Workforce Attendance Ledger')

@section('content')
    <div class="page-header">
        <div>
            <h1>Workforce Attendance Ledger</h1>
            <p>Monitor attendance, shifts, leave and workforce activity across the organization.</p>
        </div>
        <div class="header-meta">
            <div class="eyebrow">Organization</div>
            <div class="val">Iconic Social Pvt Ltd</div>
        </div>
    </div>

    <!-- ================= COMPANY METRICS ================= -->
    <div class="metrics-mega">
        <div class="section-heading">
            <div>
                <h2>Company Metrics</h2>
                <div class="sub" id="analyticsRangeLabel">Showing This Month · {{ $carbonMonth->format('F Y') }}</div>
            </div>
            <div class="range-control">
                <div class="range-seg" id="rangeSeg">
                    <button class="{{ $activeRange === 'today' ? 'active' : '' }}" data-range="today">Today</button>
                    <button class="{{ $activeRange === 'week' ? 'active' : '' }}" data-range="week">This Week</button>
                    <button class="{{ $activeRange === 'month' ? 'active' : '' }}" data-range="month">This Month</button>
                    <button class="{{ $activeRange === 'custom' ? 'active' : '' }}" data-range="custom">Custom Range</button>
                </div>
                <div class="custom-dates {{ $activeRange === 'custom' ? 'show' : '' }}" id="customDates">
                    <input type="date" id="customStart" value="{{ $startDate->format('Y-m-d') }}">
                    <span>to</span>
                    <input type="date" id="customEnd" value="{{ $endDate->format('Y-m-d') }}">
                </div>
            </div>
        </div>

        <!-- Filters: narrows the employee set client-side -->
        <div class="metrics-filterbar glass tilt-card" id="metricsFilterBar" style="--glow: rgba(173,138,62,0.25)">
            <div class="glass-edge"></div>
            <div class="search-box" style="flex:0 1 210px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="7" />
                    <path d="M21 21l-4.3-4.3" />
                </svg>
                <input type="text" id="mfSearch" placeholder="Search employee, ID or manager…">
            </div>
            <div class="mf-divider"></div>
            <span class="mf-label">Dept</span>
            <div class="mf-chipset" id="mfDeptChips"></div>
            <div class="mf-divider"></div>
            <select class="fselect" id="mfLoc">
                <option value="all">All Locations</option>
            </select>
            <select class="fselect" id="mfShift">
                <option value="all">All Shifts</option>
            </select>
            <select class="fselect" id="mfManager">
                <option value="all">All Managers</option>
            </select>
            <button class="chip reset mf-reset" id="mfReset">Reset filters</button>
        </div>

        <!-- KPI strip — each tile is a registry entry -->
        <div class="kpi-strip" id="kpiStrip"></div>

        <!-- Bento grid of charts -->
        <div class="metrics-bento" id="metricsBento"></div>
    </div>

    <!-- ================= TOOLBAR + FILTERS ================= -->
    <div class="toolbar">
        <div class="pill-group">
            <div class="month-nav">
                <button id="prevMonth">‹</button>
                <div class="label" id="monthLabel">{{ $carbonMonth->format('F Y') }}</div>
                <button id="nextMonth">›</button>
            </div>
            <button class="btn today" id="todayBtn">Today</button>
            <div class="seg" id="bottomRangeSeg" style="display: none;">
                <button class="{{ $activeRange === 'month' ? 'active' : '' }}" data-range="month">Month</button>
                <button class="{{ $activeRange === 'custom' ? 'active' : '' }}" data-range="custom">Custom Range</button>
            </div>
        </div>
        <div class="pill-group">
            <button class="btn ghost" onclick="alert('Export is not configured for daily ledger view.')">Export</button>
            <button class="btn ghost" id="bulkToggleBtn">Bulk Actions</button>
            <button class="btn" id="lockBtn"><span class="dot-ic"></span> Attendance Lock</button>
        </div>
    </div>

    <!-- Lower Filter Panel -->
    <div class="filters">
        <div class="filters-row">
            <div class="search-box">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="7" />
                    <path d="M21 21l-4.3-4.3" />
                </svg>
                <input type="text" id="lfSearch" placeholder="Search employee, ID or email…">
            </div>
            <select class="fselect" id="lfDept">
                <option value="all">Department</option>
            </select>
            <select class="fselect" id="lfDesig">
                <option value="all">Designation</option>
            </select>
            <select class="fselect" id="lfStatus">
                <option value="all">Status</option>
                <option value="present">Present</option>
                <option value="late">Late</option>
                <option value="absent">Absent</option>
                <option value="leave">Leave</option>
                <option value="wfh">WFH</option>
            </select>
            <select class="fselect" id="lfEmploymentType">
                <option value="all">Employment Type</option>
                <option value="Full-time">Full-time</option>
                <option value="Contract">Contract</option>
                <option value="Intern">Intern</option>
            </select>
            <select class="fselect" id="lfShift">
                <option value="all">Shift</option>
            </select>
            <select class="fselect" id="lfLocation">
                <option value="all">Location</option>
            </select>
            <select class="fselect" id="lfManager">
                <option value="all">Manager</option>
            </select>
            <select class="fselect" id="lfWorkMode">
                <option value="all">Work Mode</option>
                <option value="Office">Office</option>
                <option value="Hybrid">Hybrid</option>
                <option value="Remote">Remote</option>
            </select>
        </div>
        <div class="chips-row">
            <span class="chip" data-chip="late">Only Late</span>
            <span class="chip" data-chip="absent">Only Absent</span>
            <span class="chip" data-chip="leave">Only Leave</span>
            <span class="chip" data-chip="wfh">Only WFH</span>
            <span class="chip" data-chip="halfday">Only Half Day</span>
            <span class="chip" data-chip="overtime">Only Overtime</span>
            <span class="chip reset" id="resetFilters">Reset Filters</span>
        </div>
    </div>

    <!-- ================= MATRIX ================= -->
    <div class="matrix-panel">
        <div class="matrix-topbar">
            <h3>Attendance Matrix</h3>
            <div class="legend">
                <span class="li"><span class="sw" style="background:var(--present)"></span>Present</span>
                <span class="li"><span class="sw" style="background:var(--late)"></span>Late</span>
                <span class="li"><span class="sw" style="background:var(--halfday)"></span>Half Day</span>
                <span class="li"><span class="sw" style="background:var(--absent)"></span>Absent</span>
                <span class="li"><span class="sw" style="background:var(--weekoff)"></span>Weekly Off</span>
                <span class="li"><span class="sw" style="background:var(--wfh)"></span>WFH</span>
                <span class="li"><span class="sw" style="background:var(--paidleave)"></span>Paid Leave</span>
                <span class="li"><span class="sw" style="background:var(--unplanned)"></span>Unplanned Leave</span>
                <span class="li"><span class="sw" style="background:var(--birthday)"></span>Birthday Leave</span>
            </div>
        </div>
        <div class="matrix-scroll" id="matrixScroll">
            <table class="matrix" id="matrixTable"></table>
        </div>
    </div>

    <!-- Bulk Action Bottom Bar -->
    <div class="bulk-bar" id="bulkBar">
        <span class="count" id="bulkCount">0 selected</span>
        <div class="bactions">
            <button data-b="present">Present</button>
            <button data-b="absent">Absent</button>
            <button data-b="planned">Leave</button>
            <button data-b="off">Holiday</button>
            <button data-b="shift">Shift</button>
            <button data-b="wfh">WFH</button>
            <button data-b="override">Override</button>
            <button data-b="approve">Approve</button>
            <button data-b="reject">Reject</button>
            <button data-b="lock">Lock</button>
            <button data-b="unlock">Unlock</button>
        </div>
        <button class="close" id="bulkClose">×</button>
    </div>

    <div id="popover"></div>
    <div id="chartTip"></div>
    <div id="overlay"></div>

    <!-- Right Drawer -->
    <div id="drawer">
        <div class="drawer-head">
            <div>
                <h2 id="drawerName">—</h2>
                <div class="eyebrow" id="drawerSub">—</div>
            </div>
            <button class="drawer-close" id="drawerClose">×</button>
        </div>
        <div class="drawer-body">
            <div class="drawer-section">
                <h4>Status</h4>
                <span class="status-badge" id="drawerBadge">—</span>
            </div>
            <div class="drawer-section">
                <h4>Identity Context</h4>
                <div class="dgrid">
                    <div class="dfield"><div class="l">Manager</div><div class="v" id="dManager">None</div></div>
                    <div class="dfield"><div class="l">Life cycle status</div><div class="v">Active</div></div>
                </div>
            </div>
            <div class="drawer-section">
                <h4>Punch Record</h4>
                <div class="dgrid">
                    <div class="dfield">
                        <div class="l">Punch In</div>
                        <div class="v" id="dIn">—</div>
                    </div>
                    <div class="dfield">
                        <div class="l">Punch Out</div>
                        <div class="v" id="dOut">—</div>
                    </div>
                    <div class="dfield">
                        <div class="l">Working Hours</div>
                        <div class="v" id="dHrs">—</div>
                    </div>
                    <div class="dfield">
                        <div class="l">Break</div>
                        <div class="v" id="dBreak">—</div>
                    </div>
                    <div class="dfield">
                        <div class="l">Late Minutes</div>
                        <div class="v" id="dLate">—</div>
                    </div>
                    <div class="dfield">
                        <div class="l">Overtime</div>
                        <div class="v" id="dOt">—</div>
                    </div>
                </div>
            </div>
            <div class="drawer-section">
                <h4>Context</h4>
                <div class="dgrid">
                    <div class="dfield">
                        <div class="l">Shift</div>
                        <div class="v" id="dShift">—</div>
                    </div>
                    <div class="dfield">
                        <div class="l">Shift Grace</div>
                        <div class="v" id="dGrace">—</div>
                    </div>
                    <div class="dfield">
                        <div class="l">Approval</div>
                        <div class="v" id="dApproval">—</div>
                    </div>
                    <div class="dfield">
                        <div class="l">Geo Location</div>
                        <div class="v" id="dGeo">—</div>
                    </div>
                    <div class="dfield">
                        <div class="l">Source</div>
                        <div class="v" id="dSource">—</div>
                    </div>
                </div>
            </div>

            <!-- Leave Context -->
            <div class="drawer-section" id="drawerLeaveSec" style="display:none;">
                <h4>Leave Context</h4>
                <div class="dgrid">
                    <div class="dfield" style="grid-column: span 2;">
                        <div class="l">Leave Type</div>
                        <div class="v" id="dLeaveType">—</div>
                    </div>
                    <div class="dfield">
                        <div class="l">Approval State</div>
                        <div class="v" id="dLeaveStatus">—</div>
                    </div>
                    <div class="dfield">
                        <div class="l">Affected Attendance</div>
                        <div class="v" id="dLeaveAffected">—</div>
                    </div>
                </div>
            </div>

            <!-- Manual Override Context -->
            <div class="drawer-section" id="drawerOverrideSec" style="display:none;">
                <h4>Manual Override Context</h4>
                <div class="dgrid">
                    <div class="dfield">
                        <div class="l">Overridden To</div>
                        <div class="v" id="dOverrideStatus">—</div>
                    </div>
                    <div class="dfield">
                        <div class="l">Original Status</div>
                        <div class="v" id="dOverrideOriginal">—</div>
                    </div>
                    <div class="dfield" style="grid-column: span 2;">
                        <div class="l">Override Reason</div>
                        <div class="v" id="dOverrideReason">—</div>
                    </div>
                    <div class="dfield">
                        <div class="l">Override By</div>
                        <div class="v" id="dOverrideBy">—</div>
                    </div>
                    <div class="dfield">
                        <div class="l">Timestamp</div>
                        <div class="v" id="dOverrideTime">—</div>
                    </div>
                </div>
            </div>

            <!-- Payroll Synchronization -->
            <div class="drawer-section">
                <h4>Payroll Synchronization</h4>
                <div class="dgrid">
                    <div class="dfield">
                        <div class="l">Payroll Cycle</div>
                        <div class="v" id="dPayrollCycle">—</div>
                    </div>
                    <div class="dfield">
                        <div class="l">Daily Rate Basis</div>
                        <div class="v" id="dPayrollRate">—</div>
                    </div>
                    <div class="dfield">
                        <div class="l">Deduction Factor</div>
                        <div class="v" id="dPayrollFactor">—</div>
                    </div>
                    <div class="dfield">
                        <div class="l">Net Day Deduction</div>
                        <div class="v" id="dPayrollDeduction">—</div>
                    </div>
                    <div class="dfield">
                        <div class="l">Calculation Version</div>
                        <div class="v" id="dPayrollVersion">—</div>
                    </div>
                    <div class="dfield">
                        <div class="l">Disbursement Status</div>
                        <div class="v" id="dPayrollLock">—</div>
                    </div>
                </div>
            </div>

            <div class="drawer-section">
                <h4>Manager Notes</h4>
                <textarea class="notes-box" id="dNotes" placeholder="Add a note for this attendance entry…" readonly></textarea>
            </div>
            <div class="drawer-section" id="drawerAuditSec">
                <h4>Audit Trail</h4>
                <div id="dAudit"></div>
            </div>

            <!-- Apply Correction Override Form -->
            <div class="drawer-section" id="drawerCorrectionFormSec" style="display:none; border-top:1.5px dashed var(--border); padding-top:16px;">
                <h4 style="color:var(--gold);">Apply Correction Override</h4>
                <form id="overrideForm">
                    <div class="dfield" style="margin-bottom:12px; padding:6px 10px;">
                        <div class="l">New Final State</div>
                        <select class="fselect" id="correctionStatus" style="width:100%; border:none; background-color:transparent; padding:4px 0; margin-top:2px;">
                            <option value="present">Present (Full Day)</option>
                            <option value="late">Late Arrival</option>
                            <option value="half">Half Day Present</option>
                            <option value="absent">Absent (Unpaid)</option>
                            <option value="planned">Paid Leave (Planned)</option>
                            <option value="upa">Unplanned Leave</option>
                            <option value="off">Off Day</option>
                        </select>
                    </div>
                    <div class="dfield" style="margin-bottom:12px; padding:6px 10px;">
                        <div class="l">Classification</div>
                        <select class="fselect" id="correctionClass" style="width:100%; border:none; background-color:transparent; padding:4px 0; margin-top:2px;">
                            <option value="automatic">Automatic Classification</option>
                            <option value="full_day">Full Day Status</option>
                            <option value="half_day">Half Day Status</option>
                        </select>
                    </div>
                    <div class="dfield" style="padding:6px 10px; margin-bottom:16px;">
                        <div class="l">Override justification reason</div>
                        <textarea class="notes-box" id="correctionReason" placeholder="Enter reason (at least 5 characters)..." required></textarea>
                    </div>
                    <div style="display:flex; gap:10px;">
                        <button type="submit" class="btn primary" style="width:100%; justify-content:center;" id="btnSubmitOverride">Apply Override Correction</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="drawer-actions">
            <button class="btn ghost" id="btnEditAttendance">Edit Attendance</button>
            <button class="btn ghost" id="btnChangeShift">Change Shift</button>
            <button class="btn ghost" id="btnAssignLeave">Assign Leave</button>
            <button class="btn ghost" id="btnOverrideAttendance">Override Attendance</button>
            <button class="btn primary" id="btnApproveAttendance" style="background:var(--present); border-color:var(--present);">Approve</button>
            <button class="btn" id="btnRejectAttendance" style="color:var(--unplanned);border-color:var(--unplanned);">Reject</button>
        </div>
    </div>

    <!-- Bulk Action Override Modal -->
    <div id="bulkModal" class="drawer-body" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); width:460px; max-width:95vw; max-height:85vh; background:var(--panel); border:1px solid var(--border); border-radius:var(--radius-lg); z-index:90; box-shadow:var(--shadow-lg); padding:24px;">
        <div style="display:flex; justify-content:between; align-items:start; margin-bottom:16px; border-bottom:1px solid var(--border-soft); padding-bottom:10px;">
            <h3 style="font-family:'Fraunces',serif; font-size:19px;">Bulk Attendance Overrides</h3>
            <button id="bulkModalClose" style="background:none; border:none; font-size:18px; cursor:pointer; color:var(--ink-soft); margin-left:auto;">×</button>
        </div>
        <form id="bulkOverrideForm">
            <div id="bulkPreviewAlert" style="background:var(--late-bg); border:1px solid var(--late); color:var(--ink); padding:10px 14px; border-radius:8px; margin-bottom:14px; font-size:12px; display:none;"></div>
            
            <div class="dfield" style="margin-bottom:12px; padding:6px 10px;">
                <div class="l">Selected Employees count</div>
                <div class="v" id="bulkModalCount">0 selected</div>
            </div>

            <!-- Date mode -->
            <div class="dfield" style="margin-bottom:12px; padding:6px 10px;">
                <div class="l">Override Date range mode</div>
                <select class="fselect" id="bulkDateMode" style="width:100%; border:none; background-color:transparent; padding:4px 0; margin-top:2px;">
                    <option value="single">Single Date</option>
                    <option value="range">Range of Dates</option>
                </select>
            </div>

            <div id="bulkSingleDateSection" class="dfield" style="margin-bottom:12px; padding:6px 10px;">
                <div class="l">Target Date</div>
                <input type="date" id="bulkSingleDate" style="border:none; background:transparent; font-family:'JetBrains Mono',monospace; width:100%; margin-top:2px;" value="{{ $startDate->format('Y-m-d') }}">
            </div>

            <div id="bulkRangeSection" style="display:none; gap:10px; margin-bottom:12px;">
                <div class="dfield" style="flex:1; padding:6px 10px;">
                    <div class="l">From Date</div>
                    <input type="date" id="bulkStartDate" style="border:none; background:transparent; font-family:'JetBrains Mono',monospace; width:100%; margin-top:2px;" value="{{ $startDate->format('Y-m-d') }}">
                </div>
                <div class="dfield" style="flex:1; padding:6px 10px;">
                    <div class="l">To Date</div>
                    <input type="date" id="bulkEndDate" style="border:none; background:transparent; font-family:'JetBrains Mono',monospace; width:100%; margin-top:2px;" value="{{ $endDate->format('Y-m-d') }}">
                </div>
            </div>

            <!-- Options -->
            <div style="display:flex; flex-direction:column; gap:6px; margin-bottom:14px; font-size:12px; padding:4px 8px;">
                <label style="display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" id="bulkWorkingDays" checked> Apply on standard working days only
                </label>
                <label style="display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" id="bulkSkipLeaves" checked> Skip existing approved leaves
                </label>
                <label style="display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" id="bulkSkipOverrides"> Skip existing manual overrides
                </label>
            </div>

            <!-- Target status -->
            <div class="dfield" style="margin-bottom:12px; padding:6px 10px;">
                <div class="l">Target Status Override</div>
                <select class="fselect" id="bulkStatus" style="width:100%; border:none; background-color:transparent; padding:4px 0; margin-top:2px;">
                    <option value="present">Present (Full Day)</option>
                    <option value="late">Late Arrival</option>
                    <option value="half">Half Day Present</option>
                    <option value="absent">Absent (Unpaid)</option>
                    <option value="planned">Paid Leave (Planned)</option>
                    <option value="upa">Unplanned Leave</option>
                    <option value="off">Off Day</option>
                </select>
            </div>

            <div class="dfield" style="margin-bottom:12px; padding:6px 10px;">
                <div class="l">Classification</div>
                <select class="fselect" id="bulkClass" style="width:100%; border:none; background-color:transparent; padding:4px 0; margin-top:2px;">
                    <option value="automatic">Automatic Classification</option>
                    <option value="full_day">Full Day Status</option>
                    <option value="half_day">Half Day Status</option>
                </select>
            </div>

            <div class="dfield" style="margin-bottom:16px; padding:6px 10px;">
                <div class="l">Justification notes</div>
                <textarea class="notes-box" id="bulkReason" placeholder="Enter reason (at least 5 characters)..." required></textarea>
            </div>

            <div style="display:flex; gap:10px;">
                <button type="button" class="btn ghost" id="btnPreviewBulk" style="flex:1; justify-content:center;">Check Conflicts</button>
                <button type="submit" class="btn primary" id="btnSubmitBulk" style="flex:1; justify-content:center;" disabled>Apply Override</button>
            </div>
        </form>
    </div>

    <!-- Dynamic JavaScript hydration logic -->
    <script>
        // Real database records preloaded from controller
        const laravelDateList = @json(collect($dateList)->map(fn($d) => $d->format('Y-m-d')));
        const laravelEmployees = [
            @foreach($matrix as $empId => $data)
            @php
                $emp = $data['employee'];
                $empInitials = collect(explode(' ', $emp->name))->map(fn($w) => substr($w, 0, 1))->take(2)->join('') ?? '';
            @endphp
            {
                id: "{{ $emp->id }}",
                employee_id: "{{ $emp->employee_id }}",
                name: "{{ addslashes($emp->name) }}",
                dept: "{{ $emp->department->name ?? 'Unassigned' }}",
                desig: "{{ $emp->employeeProfile->designation ?? ($emp->employee_profile->designation ?? 'Employee') }}",
                shift: "{{ $emp->employeeProfile->shift ?? ($emp->employee_profile->shift ?? 'Regular Shift') }}",
                loc: "{{ $emp->employeeProfile->location ?? ($emp->employee_profile->location ?? 'HQ, Dehradun') }}",
                manager: "{{ $emp->manager->name ?? 'None' }}",
                initials: "{{ $empInitials }}",
                employment_type: "{{ $emp->employeeProfile->employment_type ?? 'Full-time' }}",
                entity: "{{ $emp->employeeProfile->entity ?? 'IN-DEL-01' }}",
                work_mode: "{{ $emp->employeeProfile->work_mode ?? 'Office' }}"
            },
            @endforeach
        ];

        const laravelAttendanceData = {
            @foreach($matrix as $empId => $data)
            "{{ $empId }}": [
                @foreach($dateList as $date)
                @php
                    $dStr = $date->format('Y-m-d');
                    $resolved = $data['dates'][$dStr];
                    $lateMinutes = 0;
                    if ($resolved['check_in_time'] && $resolved['shift_start']) {
                        $ci = \Carbon\Carbon::parse($resolved['check_in_time']);
                        $ss = \Carbon\Carbon::parse($resolved['shift_start']);
                        if ($ci->greaterThan($ss)) {
                            $lateMinutes = $ci->diffInMinutes($ss);
                        }
                    }
                    
                    // Map resolved status and label to reference names
                    $status = $resolved['status'];
                    $cellClass = 'absent';
                    $label = 'Absent';
                    if (in_array($status, ['present', 'late'])) {
                        $cellClass = $status;
                        $label = ucfirst($status);
                    } elseif (in_array($status, ['half', 'hd_upr', 'hd_upa', 'hdp'])) {
                        $cellClass = 'halfday';
                        $label = 'Half Day';
                    } elseif ($status === 'wfh') {
                        $cellClass = 'wfh';
                        $label = 'Remote';
                    } elseif (in_array($status, ['planned', 'upa'])) {
                        $cellClass = $status === 'planned' ? 'paidleave' : 'unplanned';
                        $label = $status === 'planned' ? 'Paid Leave' : 'Unplanned Leave';
                    } elseif ($status === 'bday') {
                        $cellClass = 'birthday';
                        $label = 'Birthday Leave';
                    } elseif ($status === 'off') {
                        $cellClass = 'weekoff';
                        $label = 'Weekly Off';
                    }
                    
                    $hrsStr = $resolved['hours'] > 0 ? $resolved['hours'] . 'h' : '';
                    $otStr = $resolved['check_in_time'] && $resolved['check_out_time'] && $resolved['hours'] > 8 ? round($resolved['hours'] - 8, 1) . 'h' : '0h';
                @endphp
                {
                    date: "{{ $dStr }}",
                    day: {{ $date->day }},
                    status: "{{ $cellClass }}",
                    label: "{{ $label }}",
                    hrs: "{{ $hrsStr }}",
                    inT: "{{ $resolved['check_in_time'] ? \Carbon\Carbon::parse($resolved['check_in_time'])->format('h:i A') : '' }}",
                    outT: "{{ $resolved['check_out_time'] ? \Carbon\Carbon::parse($resolved['check_out_time'])->format('h:i A') : '' }}",
                    ot: "{{ $otStr }}",
                    lateMin: {{ $lateMinutes }},
                    override: {{ $resolved['is_overridden'] ? 'true' : 'false' }},
                    notes: "{{ addslashes($resolved['notes'] ?? '') }}",
                    approved_by: "{{ addslashes($resolved['approved_by'] ?? '') }}",
                    shift_start: "{{ $resolved['shift_start'] ? \Carbon\Carbon::parse($resolved['shift_start'])->format('h:i A') : '' }}",
                    shift_end: "{{ $resolved['shift_end'] ? \Carbon\Carbon::parse($resolved['shift_end'])->format('h:i A') : '' }}",
                    grace_minutes: {{ $resolved['timings']['grace_minutes'] ?? 0 }},
                    
                    original_status: "{{ $resolved['original_status'] ?? '' }}",
                    override_reason: "{{ addslashes($resolved['override_reason'] ?? '') }}",
                    override_by: "{{ addslashes($resolved['override_by_user']->name ?? '') }}",
                    override_time: "{{ $resolved['override_time'] ?? '' }}"
                },
                @endforeach
            ],
            @endforeach
        };

        // Authoritative Lists populated from database values
        const DEPTS = [
            @foreach($departments as $dept)
                "{{ $dept->name }}",
            @endforeach
        ];
        const SHIFTS = [
            @foreach($allActiveShifts as $shift)
                "{{ $shift }}",
            @endforeach
        ];
        const LOCS = [
            @foreach($allLocations as $loc)
                "{{ $loc }}",
            @endforeach
        ];
        const MANAGERS = [
            @foreach(collect($matrix)->map(fn($m) => $m['employee']->manager->name ?? '')->filter()->unique()->values() as $mgrName)
                "{{ addslashes($mgrName) }}",
            @endforeach
        ];

        const DESIGNS = [
            @foreach(collect($matrix)->map(fn($m) => $m['employee']->employeeProfile->designation ?? '')->filter()->unique()->values() as $desig)
                "{{ addslashes($desig) }}",
            @endforeach
        ];

        const HOLIDAYS = { "2026-07-17": "Founders Day" };
        var isPayrollAuthorized = {{ (Auth::user() && Auth::user()->role === 'admin') ? 'true' : 'false' }};
        let mFilters = { dept: new Set(), loc: 'all', shift: 'all', manager: 'all', desig: 'all', employment_type: 'all', entity: 'all', work_mode: 'all', search: '', onlyStatus: null };
        
        let employees = laravelEmployees;
        let dateList = laravelDateList;
        let activeRange = "{{ $activeRange }}";
        let currentDate = new Date("{{ $carbonMonth->format('Y-m-d') }}");
        let today = new Date("{{ today()->format('Y-m-d') }}");

        function daysInMonth(d) { return new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate(); }
        function fmtDate(y, m, day) { return `${y}-${String(m + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`; }

        let attendance = {};
        function generateAttendance() {
            attendance = {};
            const y = currentDate.getFullYear(), m = currentDate.getMonth();
            const nd = daysInMonth(currentDate);
            
            employees.forEach(emp => {
                const rows = [];
                for (let day = 1; day <= nd; day++) {
                    const dow = new Date(y, m, day).getDay();
                    const isWeekend = (dow === 0 || dow === 6);
                    const key = fmtDate(y, m, day);
                    const isHoliday = !!HOLIDAYS[key];
                    
                    rows.push({
                        status: isHoliday ? 'holiday' : (isWeekend ? 'weekoff' : 'absent'),
                        label: isHoliday ? 'Holiday' : (isWeekend ? 'Weekly Off' : 'Absent'),
                        date: key,
                        day: day,
                        override: false
                    });
                }
                attendance[emp.id] = rows;
            });

            // Overlay database records
            for (const empId in laravelAttendanceData) {
                if (attendance[empId]) {
                    laravelAttendanceData[empId].forEach(c => {
                        const dayIndex = c.day - 1;
                        if (dayIndex >= 0 && dayIndex < attendance[empId].length) {
                            attendance[empId][dayIndex] = c;
                        }
                    });
                }
            }
        }
        generateAttendance();

        /* =====================================================================
           COMPANY METRICS ENGINE
        ===================================================================== */
        const chartTip = document.getElementById('chartTip');
        function showTip(e, html) { chartTip.innerHTML = html; chartTip.style.display = 'block'; moveTip(e); }
        function moveTip(e) {
            const pad = 14; let x = e.clientX + pad, y = e.clientY + pad;
            const vw = window.innerWidth, vh = window.innerHeight;
            if (x + 220 > vw) x = e.clientX - 220 - pad;
            if (y + 90 > vh) y = e.clientY - 90 - pad;
            chartTip.style.left = x + 'px'; chartTip.style.top = y + 'px';
        }
        function hideTip() { chartTip.style.display = 'none'; }

        function getFilteredEmployees() {
            const q = mFilters.search.trim().toLowerCase();
            return employees.filter(emp => {
                if (mFilters.dept.size && !mFilters.dept.has(emp.dept)) return false;
                if (mFilters.loc !== 'all' && emp.loc !== mFilters.loc) return false;
                if (mFilters.shift !== 'all' && emp.shift !== mFilters.shift) return false;
                if (mFilters.manager !== 'all' && emp.manager !== mFilters.manager) return false;
                if (mFilters.desig !== 'all' && emp.desig !== mFilters.desig) return false;
                if (mFilters.employment_type !== 'all' && emp.employment_type !== mFilters.employment_type) return false;
                if (mFilters.entity !== 'all' && emp.entity !== mFilters.entity) return false;
                if (mFilters.work_mode !== 'all' && emp.work_mode !== mFilters.work_mode) return false;
                
                if (q && !(emp.name.toLowerCase().includes(q) || emp.id.toLowerCase().includes(q) || emp.manager.toLowerCase().includes(q))) return false;
                
                if (mFilters.onlyStatus) {
                    const records = attendance[emp.id] || [];
                    const hasStatus = records.some(r => {
                        if (mFilters.onlyStatus === 'late') return r.status === 'late';
                        if (mFilters.onlyStatus === 'absent') return r.status === 'absent';
                        if (mFilters.onlyStatus === 'leave') return ['paidleave', 'unplanned', 'birthday', 'halfday'].includes(r.status);
                        if (mFilters.onlyStatus === 'wfh') return r.status === 'wfh';
                        if (mFilters.onlyStatus === 'halfday') return r.status === 'halfday';
                        if (mFilters.onlyStatus === 'overtime') return parseFloat(r.ot) > 0;
                        return false;
                    });
                    if (!hasStatus) return false;
                }
                return true;
            });
        }

        function getSelectedDays() {
            const nd = daysInMonth(currentDate);
            const inThisMonth = (today.getFullYear() === currentDate.getFullYear() && today.getMonth() === currentDate.getMonth());
            if (activeRange === 'today') {
                return [inThisMonth ? today.getDate() : 1];
            }
            if (activeRange === 'week') {
                const end = inThisMonth ? today.getDate() : nd;
                const start = Math.max(1, end - 6);
                const arr = []; for (let d = start; d <= end; d++) arr.push(d);
                return arr;
            }
            if (activeRange === 'custom') {
                const sVal = document.getElementById('customStart').value;
                const eVal = document.getElementById('customEnd').value;
                if (sVal && eVal) {
                    const s = new Date(sVal), e = new Date(eVal);
                    const arr = [];
                    for (let d = 1; d <= nd; d++) {
                        const dd = new Date(currentDate.getFullYear(), currentDate.getMonth(), d);
                        if (dd >= new Date(s.getFullYear(), s.getMonth(), s.getDate()) && dd <= new Date(e.getFullYear(), e.getMonth(), e.getDate())) arr.push(d);
                    }
                    return arr.length ? arr : Array.from({ length: nd }, (_, i) => i + 1);
                }
                return Array.from({ length: nd }, (_, i) => i + 1);
            }
            return Array.from({ length: nd }, (_, i) => i + 1);
        }

        function getPreviousDays(days) {
            const len = days.length;
            const start = Math.min(...days), end = Math.max(...days);
            let prevEnd = start - 1, prevStart = prevEnd - len + 1;
            if (prevStart < 1) { prevStart = 1; prevEnd = start - 1; }
            if (prevEnd < prevStart) return null;
            const arr = []; for (let d = prevStart; d <= prevEnd; d++) arr.push(d);
            return arr;
        }

        function computeMetrics(days, emps) {
            const y = currentDate.getFullYear(), m = currentDate.getMonth();

            const trend = days.map(day => {
                const s = { day, present: 0, late: 0, wfh: 0, absent: 0, leave: 0, weekoff: 0, hrsSum: 0, hrsCount: 0, otSum: 0 };
                emps.forEach(emp => {
                    const c = attendance[emp.id][day - 1]; if (!c) return;
                    if (c.status === 'present') { s.present++; s.hrsSum += parseFloat(c.hrs); s.hrsCount++; if (c.ot) s.otSum += parseFloat(c.ot); }
                    else if (c.status === 'late') { s.late++; s.hrsSum += parseFloat(c.hrs); s.hrsCount++; }
                    else if (c.status === 'wfh') { s.wfh++; s.hrsSum += parseFloat(c.hrs); s.hrsCount++; }
                    else if (c.status === 'absent') { s.absent++; }
                    else if (c.status === 'weekoff' || c.status === 'holiday') { s.weekoff++; }
                    else { s.leave++; }
                });
                s.ot = s.otSum;
                s.hrsAvg = s.hrsCount ? (s.hrsSum / s.hrsCount) : 0;
                return s;
            });

            const totals = trend.reduce((a, s) => {
                a.present += s.present; a.late += s.late; a.wfh += s.wfh; a.absent += s.absent; a.leave += s.leave; a.weekoff += s.weekoff;
                a.hrsSum += s.hrsSum; a.hrsCount += s.hrsCount; a.otSum += s.otSum;
                return a;
            }, { present: 0, late: 0, wfh: 0, absent: 0, leave: 0, weekoff: 0, hrsSum: 0, hrsCount: 0, otSum: 0 });

            const healthTotal = totals.present + totals.late + totals.wfh + totals.leave + totals.absent;
            const pct = healthTotal ? ((totals.present + totals.late + totals.wfh) / healthTotal * 100) : 0;
            const absentRate = healthTotal ? (totals.absent / healthTotal * 100) : 0;

            const leaveTypes = { paidleave: 0, unplanned: 0, birthday: 0, halfday: 0 };
            const punct = { onTime: 0, lateSoft: 0, lateHard: 0 };
            const perEmp = {}, byDept = {}, byManager = {}, byLoc = {}, byShift = {};
            const dowAgg = [0, 1, 2, 3, 4, 5, 6].map(() => ({ present: 0, total: 0 }));

            emps.forEach(emp => {
                perEmp[emp.id] = { emp, present: 0, late: 0, wfh: 0, absent: 0, leave: 0, hrsSum: 0, hrsCount: 0, otSum: 0 };
                if (!byDept[emp.dept]) byDept[emp.dept] = { present: 0, late: 0, wfh: 0, absent: 0, leave: 0, total: 0, hrsSum: 0, hrsCount: 0, otSum: 0, headcount: 0 };
                byDept[emp.dept].headcount++;
                if (!byManager[emp.manager]) byManager[emp.manager] = { present: 0, late: 0, wfh: 0, absent: 0, leave: 0, total: 0, headcount: 0, pending: 0 };
                byManager[emp.manager].headcount++;
                byLoc[emp.loc] = (byLoc[emp.loc] || 0) + 1;
                byShift[emp.shift] = (byShift[emp.shift] || 0) + 1;
            });

            days.forEach(day => {
                const dow = new Date(y, m, day).getDay();
                emps.forEach(emp => {
                    const c = attendance[emp.id][day - 1]; if (!c) return;
                    const pe = perEmp[emp.id], sd = byDept[emp.dept], sm = byManager[emp.manager];
                    const isWorkable = !(c.status === 'weekoff' || c.status === 'holiday');
                    if (isWorkable) { sd.total++; sm.total++; dowAgg[dow].total++; }
                    if (c.status === 'present') {
                        pe.present++; sd.present++; sm.present++; dowAgg[dow].present++;
                        const h = parseFloat(c.hrs); pe.hrsSum += h; pe.hrsCount++; sd.hrsSum += h; sd.hrsCount++;
                        if (c.ot) { const ot = parseFloat(c.ot); pe.otSum += ot; sd.otSum += ot; }
                        punct.onTime++;
                    } else if (c.status === 'late') {
                        pe.late++; sd.late++; sm.late++; dowAgg[dow].present++;
                        const h = parseFloat(c.hrs); pe.hrsSum += h; pe.hrsCount++; sd.hrsSum += h; sd.hrsCount++;
                        const lm = c.lateMin || 0;
                        if (lm < 20) punct.lateSoft++; else punct.lateHard++;
                        if (lm >= 20) sm.pending++;
                    } else if (c.status === 'wfh') {
                        pe.wfh++; sd.wfh++; sm.wfh++; dowAgg[dow].present++;
                        const h = parseFloat(c.hrs); pe.hrsSum += h; pe.hrsCount++; sd.hrsSum += h; sd.hrsCount++;
                        sm.pending++;
                    } else if (c.status === 'absent') {
                        pe.absent++; sd.absent++; sm.absent++;
                    } else if (['paidleave', 'unplanned', 'birthday', 'halfday'].includes(c.status)) {
                        pe.leave++; sd.leave++; sm.leave++;
                        leaveTypes[c.status] = (leaveTypes[c.status] || 0) + 1;
                    }
                });
            });

            const payrollRate = 380; 
            const payrollCost = totals.hrsSum * payrollRate;

            return {
                employees: emps.length,
                present: totals.present, late: totals.late, wfh: totals.wfh, absent: totals.absent, leave: totals.leave, weekoff: totals.weekoff,
                pct: pct.toFixed(1), absentRate: absentRate.toFixed(1),
                avgHrs: totals.hrsCount ? (totals.hrsSum / totals.hrsCount).toFixed(1) : '0.0',
                otSum: totals.otSum,
                pending: Object.values(byManager).reduce((a, mm) => a + mm.pending, 0),
                payrollDays: days.filter(d => { const dow = new Date(y, m, d).getDay(); const key = fmtDate(y, m, d); return dow !== 0 && dow !== 6 && !HOLIDAYS[key]; }).length,
                payrollCost,
                trend, leaveTypes, punct, perEmp, byDept, byManager, byLoc, byShift, dowAgg
            };
        }

        function sparklinePath(vals) {
            if (!vals || !vals.length) return '';
            const w = 120, h = 28, pad = 2;
            const max = Math.max(...vals, 1), min = Math.min(...vals, 0);
            const stepX = (w - pad * 2) / ((vals.length - 1) || 1);
            const pts = vals.map((v, i) => [pad + i * stepX, h - pad - ((v - min) / ((max - min) || 1)) * (h - pad * 2)]);
            const line = pts.map((p, i) => (i === 0 ? 'M' : 'L') + p[0].toFixed(1) + ',' + p[1].toFixed(1)).join(' ');
            const area = line + ` L${pts[pts.length - 1][0].toFixed(1)},${h} L${pts[0][0].toFixed(1)},${h} Z`;
            return `<path d="${area}" fill="rgba(173,138,62,0.18)"/><path d="${line}" fill="none" stroke="#AD8A3E" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>`;
        }
        function labelForRange(days) { return days.length + (days.length === 1 ? ' day' : ' days') + ' in range'; }
        function heatColor(v) {
            if (v === null) return 'var(--border-soft)';
            const t = Math.max(0, Math.min(1, v / 100));
            const from = [244, 235, 214], to = [133, 96, 32];
            const rgb = from.map((c, i) => Math.round(c + (to[i] - c) * t));
            return `rgb(${rgb.join(',')})`;
        }
        function attachGlassGlow(selector) {
            document.querySelectorAll(selector).forEach(el => {
                if (el._glowAttached) return; el._glowAttached = true;
                el.addEventListener('mousemove', e => {
                    const r = el.getBoundingClientRect();
                    el.style.setProperty('--mx', ((e.clientX - r.left) / r.width * 100) + '%');
                    el.style.setProperty('--my', ((e.clientY - r.top) / r.height * 100) + '%');
                });
            });
        }

        /* =====================================================================
           KPI STRIP & BENTO GRID RENDERERS
        ===================================================================== */
        const KPI_TILES = [
            { key: 'headcount', label: 'Active Headcount', suffix: '', glow: 'rgba(173,138,62,0.35)', get: M => M.employees, spark: M => M.trend.map(s => s.present + s.late + s.wfh) },
            { key: 'attendance', label: 'Attendance Rate', suffix: '%', glow: 'rgba(76,122,93,0.35)', get: M => M.pct, spark: M => M.trend.map(s => s.present + s.late + s.wfh) },
            { key: 'avgHrs', label: 'Avg Hours / Day', suffix: 'h', glow: 'rgba(125,98,150,0.3)', get: M => M.avgHrs, spark: M => M.trend.map(s => s.hrsAvg) },
            { key: 'ot', label: 'Overtime Hours', suffix: 'h', glow: 'rgba(190,106,52,0.3)', get: M => M.otSum.toFixed(1), spark: M => M.trend.map(s => s.ot) },
            { key: 'absent', label: 'Absenteeism Rate', suffix: '%', glow: 'rgba(156,59,59,0.3)', get: M => M.absentRate, spark: M => M.trend.map(s => s.absent) },
            { key: 'pending', label: 'Pending Approvals', suffix: '', glow: 'rgba(173,138,62,0.3)', get: M => M.pending, spark: null },
        ];

        function renderKpiStrip(M, PM, days) {
            const strip = document.getElementById('kpiStrip');
            strip.innerHTML = KPI_TILES.map(k => {
                const val = k.get(M);
                let deltaHtml = '';
                if (PM) {
                    const prevVal = parseFloat(k.get(PM)), curVal = parseFloat(val);
                    if (!isNaN(prevVal) && !isNaN(curVal)) {
                        const diff = curVal - prevVal;
                        const inverse = (k.key === 'absent' || k.key === 'ot' || k.key === 'pending');
                        const dir = Math.abs(diff) < 0.05 ? 'flat' : (diff > 0 ? 'up' : 'down');
                        const cls = dir === 'flat' ? 'flat' : (inverse ? (dir === 'up' ? 'down' : 'up') : dir);
                        const arrow = dir === 'flat' ? '·' : (dir === 'up' ? '▲' : '▼');
                        deltaHtml = `<span class="kpi-delta ${cls}">${arrow} ${Math.abs(diff).toFixed(1)}${k.suffix}</span>`;
                    }
                }
                const sparkVals = k.spark ? k.spark(M) : null;
                return `<div class="kpi-tile glass tilt-card" style="--glow:${k.glow}">
                    <div class="glass-edge"></div>
                    <div class="kpi-top"><span class="eyebrow">${k.label}</span>${deltaHtml}</div>
                    <div class="kpi-val">${val}${k.suffix}</div>
                    <div class="kpi-lbl">${labelForRange(days)}</div>
                    ${sparkVals ? `<svg class="kpi-spark" viewBox="0 0 120 28" preserveAspectRatio="none">${sparklinePath(sparkVals)}</svg>` : ''}
                </div>`;
            }).join('');
        }

        function renderTrendArea(host, M) {
            const series = [
                { k: 'present', label: 'Present', c: '#4C7A5D' },
                { k: 'late', label: 'Late', c: '#B98A2E' },
                { k: 'wfh', label: 'WFH', c: '#4A6FA0' },
                { k: 'leave', label: 'Leave', c: '#AD8A3E' },
                { k: 'absent', label: 'Absent', c: '#9C3B3B' },
            ];
            const w = 680, h = 190, pad = 6;
            const n = M.trend.length;
            const stepX = n > 1 ? (w - pad * 2) / (n - 1) : 0;
            const totals = M.trend.map(s => series.reduce((a, se) => a + s[se.k], 0));
            const maxTotal = Math.max(...totals, 1);
            const cum = new Array(n).fill(0);
            let paths = '';
            series.forEach(se => {
                const topPts = [], botPts = [];
                for (let i = 0; i < n; i++) {
                    const bottom = cum[i];
                    const top = bottom + M.trend[i][se.k];
                    const x = pad + i * stepX;
                    topPts.push([x, h - pad - (top / maxTotal) * (h - pad * 2)]);
                    botPts.push([x, h - pad - (bottom / maxTotal) * (h - pad * 2)]);
                    cum[i] = top;
                }
                const topLine = topPts.map((p, i) => (i === 0 ? 'M' : 'L') + p[0].toFixed(1) + ',' + p[1].toFixed(1)).join(' ');
                const botLine = botPts.slice().reverse().map(p => 'L' + p[0].toFixed(1) + ',' + p[1].toFixed(1)).join(' ');
                paths += `<path class="trend-band" d="${topLine} ${botLine} Z" fill="${se.c}" fill-opacity="0.72" stroke="${se.c}" stroke-width="0.5"/>`;
            });
            let hoverRects = '';
            for (let i = 0; i < n; i++) {
                const x = pad + i * stepX;
                hoverRects += `<rect class="trend-hover" data-i="${i}" x="${(x - stepX / 2).toFixed(1)}" y="0" width="${(stepX || w).toFixed(1)}" height="${h}" fill="transparent"/>`;
            }
            host.innerHTML = `<svg width="100%" height="${h}" viewBox="0 0 ${w} ${h}" preserveAspectRatio="none">${paths}${hoverRects}</svg>
                <div class="mlegend">${series.map(se => `<span class="li"><span class="sw" style="background:${se.c}"></span>${se.label}</span>`).join('')}</div>`;
            host.querySelectorAll('.trend-hover').forEach(r => {
                r.addEventListener('mousemove', e => {
                    const i = +r.dataset.i, s = M.trend[i];
                    showTip(e, `<div class="tt-title">Day ${s.day}</div>` + series.map(se => `<div class="tt-row"><span><span class="tt-sw" style="background:${se.c}"></span>${se.label}</span><b>${s[se.k]}</b></div>`).join(''));
                });
                r.addEventListener('mouseleave', hideTip);
            });
        }

        function renderHealthDonut(host, M) {
            const segs = [
                { k: 'present', label: 'Present', v: M.present, c: '#4C7A5D' },
                { k: 'late', label: 'Late', v: M.late, c: '#B98A2E' },
                { k: 'wfh', label: 'WFH', v: M.wfh, c: '#4A6FA0' },
                { k: 'leave', label: 'Leave', v: M.leave, c: '#AD8A3E' },
                { k: 'absent', label: 'Absent', v: M.absent, c: '#9C3B3B' },
            ];
            const total = segs.reduce((a, b) => a + b.v, 0) || 1;
            const r = 52, cx = 64, cy = 64, circ = 2 * Math.PI * r;
            let offset = 0, svg = `<circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="#EFE9DC" stroke-width="15"/>`;
            segs.forEach(seg => {
                const frac = seg.v / total, dash = frac * circ;
                if (dash > 0) {
                    svg += `<circle class="donut-arc" data-v="${seg.v}" data-label="${seg.label}" data-pct="${(frac * 100).toFixed(1)}" cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="${seg.c}" stroke-width="15" stroke-dasharray="${dash} ${circ - dash}" stroke-dashoffset="${-offset}" transform="rotate(-90 ${cx} ${cy})" style="cursor:default;"/>`;
                }
                offset += dash;
            });
            svg += `<text x="${cx}" y="${cy - 2}" text-anchor="middle" font-family="Fraunces, serif" font-size="20" font-weight="500" fill="#2A241C">${M.pct}%</text>`;
            svg += `<text x="${cx}" y="${cy + 15}" text-anchor="middle" font-family="Inter, sans-serif" font-size="8.5" letter-spacing="0.05em" fill="#A69C89">HEALTHY DAYS</text>`;
            host.innerHTML = `<div class="donut-wrap"><svg width="128" height="128" viewBox="0 0 128 128">${svg}</svg>
                <div class="donut-legend">${segs.map(s => `<div class="li"><span class="sw" style="background:${s.c}"></span>${s.label}<b>${s.v}</b></div>`).join('')}</div></div>`;
            host.querySelectorAll('.donut-arc').forEach(arc => {
                arc.addEventListener('mousemove', e => showTip(e, `<div class="tt-title">${arc.dataset.label}</div><div class="tt-row"><span>Days</span><b>${arc.dataset.v}</b></div><div class="tt-row"><span>Share</span><b>${arc.dataset.pct}%</b></div>`));
                arc.addEventListener('mouseleave', hideTip);
            });
        }

        function renderDeptHeatmap(host, M, days, emps) {
            const deptsPresent = [...new Set(emps.map(e => e.dept))].sort();
            if (!deptsPresent.length) { host.innerHTML = '<div class="mtile-empty">No employees match the current filters.</div>'; return; }
            const rows = deptsPresent.map(dept => {
                const deptEmps = emps.filter(e => e.dept === dept);
                const row = days.map(day => {
                    let present = 0, total = 0;
                    deptEmps.forEach(emp => {
                        const c = attendance[emp.id][day - 1]; if (!c) return;
                        if (c.status === 'weekoff' || c.status === 'holiday') return;
                        total++;
                        if (c.status === 'present' || c.status === 'late' || c.status === 'wfh') present++;
                    });
                    return total ? Math.round(present / total * 100) : null;
                });
                return { dept, row };
            });
            const cols = days.length;
            host.innerHTML = `
                <div class="heat-daylabels" style="grid-template-columns:76px repeat(${cols},1fr);">
                    <span></span>${days.map(d => `<span>${d}</span>`).join('')}
                </div>
                <div class="heat-grid">
                    ${rows.map(r => `
                        <div class="heat-row" style="grid-template-columns:76px repeat(${cols},1fr);">
                            <div class="heat-rowlabel">${r.dept}</div>
                            ${r.row.map((v, i) => `<div class="heat-cell" data-dept="${r.dept}" data-day="${days[i]}" data-v="${v === null ? '—' : v}" style="background:${heatColor(v)}"></div>`).join('')}
                        </div>`).join('')}
                </div>`;
            host.querySelectorAll('.heat-cell').forEach(cell => {
                cell.addEventListener('mousemove', e => showTip(e, `<div class="tt-title">${cell.dataset.dept} · Day ${cell.dataset.day}</div><div class="tt-row"><span>Attendance</span><b>${cell.dataset.v}${cell.dataset.v === '—' ? '' : '%'}</b></div>`));
                cell.addEventListener('mouseleave', hideTip);
            });
        }

        function renderLeaveBreakdown(host, M) {
            const labels = { paidleave: 'Paid Leave', unplanned: 'Unplanned', birthday: 'Birthday', halfday: 'Half Day' };
            const colors = { paidleave: '#AD8A3E', unplanned: '#7A2E2E', birthday: '#7D6296', halfday: '#BE6A34' };
            const keys = Object.keys(labels);
            const total = keys.reduce((a, k) => a + (M.leaveTypes[k] || 0), 0) || 1;
            const bar = keys.map(k => {
                const v = M.leaveTypes[k] || 0, pct = v / total * 100;
                return pct <= 0 ? '' : `<div class="stack-seg leave-seg" data-k="${k}" data-v="${v}" data-pct="${pct.toFixed(1)}" style="width:${pct}%; background:${colors[k]};"></div>`;
            }).join('');
            host.innerHTML = `
                <div style="font-family:'Fraunces',serif; font-size:26px; font-weight:500; margin-bottom:4px;">${total} <span style="font-size:12px; font-family:'Inter',sans-serif; color:var(--ink-soft); font-weight:400;">leave days taken</span></div>
                <div class="stack-bar" style="margin-top:8px;">${bar}</div>
                <div class="mlegend">${keys.map(k => `<span class="li"><span class="sw" style="background:${colors[k]}"></span>${labels[k]}<b style="font-family:'JetBrains Mono',monospace; margin-left:4px; color:var(--ink);">${M.leaveTypes[k] || 0}</b></span>`).join('')}</div>`;
            host.querySelectorAll('.leave-seg').forEach(seg => {
                seg.addEventListener('mousemove', e => showTip(e, `<div class="tt-title">${labels[seg.dataset.k]}</div><div class="tt-row"><span>Days</span><b>${seg.dataset.v}</b></div><div class="tt-row"><span>Share</span><b>${seg.dataset.pct}%</b></div>`));
                seg.addEventListener('mouseleave', hideTip);
            });
        }

        function renderDeptRadar(host, M) {
            const depts = Object.keys(M.byDept);
            if (!depts.length) { host.innerHTML = '<div class="mtile-empty">No departments in the current filter.</div>'; return; }
            const axes = ['Attendance', 'Punctuality', 'Hours', 'OT Control', 'Reliability'];
            const palette = ['#AD8A3E', '#4A6FA0', '#4C7A5D', '#7D6296', '#BE6A34', '#9C3B3B', '#8F887A', '#B98A2E'];
            const cx = 110, cy = 104, R = 78;
            const angle = i => (Math.PI * 2 * i / axes.length) - Math.PI / 2;
            let axesSvg = '';
            axes.forEach((a, i) => {
                const ang = angle(i), x = cx + R * Math.cos(ang), y = cy + R * Math.sin(ang);
                axesSvg += `<line x1="${cx}" y1="${cy}" x2="${x.toFixed(1)}" y2="${y.toFixed(1)}" stroke="var(--border)" stroke-width="1"/>`;
            });
            [0.25, 0.5, 0.75, 1].forEach(f => {
                const pts = axes.map((a, i) => { const ang = angle(i); return `${(cx + R * f * Math.cos(ang)).toFixed(1)},${(cy + R * f * Math.sin(ang)).toFixed(1)}`; });
                axesSvg += `<polygon points="${pts.join(' ')}" fill="none" stroke="var(--border-soft)" stroke-width="1"/>`;
            });
            let polys = '', legend = '';
            depts.forEach((d, di) => {
                const s = M.byDept[d];
                const attendancePct = s.total ? (s.present + s.late + s.wfh) / s.total * 100 : 0;
                const punctualityPct = (s.present + s.late) ? s.present / (s.present + s.late) * 100 : 100;
                const hoursScore = s.hrsCount ? Math.min(100, (s.hrsSum / s.hrsCount) / 9 * 100) : 0;
                const otScore = 100 - Math.min(100, (s.otSum / (s.headcount || 1)) * 20);
                const reliabilityScore = s.total ? 100 - (s.absent / s.total * 100) : 100;
                const vals = [attendancePct, punctualityPct, hoursScore, otScore, reliabilityScore];
                const pts = vals.map((v, i) => { const ang = angle(i), f = Math.max(0, Math.min(1, v / 100)); return `${(cx + R * f * Math.cos(ang)).toFixed(1)},${(cy + R * f * Math.sin(ang)).toFixed(1)}`; });
                const color = palette[di % palette.length];
                polys += `<polygon class="radar-poly" data-dept="${d}" points="${pts.join(' ')}" fill="${color}" fill-opacity="0.12" stroke="${color}" stroke-width="1.8" style="transition:opacity .15s ease; cursor:default;"/>`;
                legend += `<span class="li radar-leg" data-dept="${d}"><span class="sw" style="background:${color}"></span>${d}</span>`;
            });
            const labelsSvg = axes.map((a, i) => {
                const ang = angle(i), x = cx + (R + 18) * Math.cos(ang), y = cy + (R + 18) * Math.sin(ang);
                return `<text x="${x.toFixed(1)}" y="${y.toFixed(1)}" text-anchor="middle" dominant-baseline="middle" font-size="9" fill="var(--ink-faint)" font-family="Inter, sans-serif">${a}</text>`;
            }).join('');
            host.innerHTML = `<svg width="100%" height="208" viewBox="0 0 220 208">${axesSvg}${polys}${labelsSvg}</svg><div class="mlegend">${legend}</div>`;
            host.querySelectorAll('.radar-leg').forEach(leg => {
                leg.addEventListener('mouseenter', () => { host.querySelectorAll('.radar-poly').forEach(p => p.style.opacity = p.dataset.dept === leg.dataset.dept ? '1' : '0.15'); });
                leg.addEventListener('mouseleave', () => { host.querySelectorAll('.radar-poly').forEach(p => p.style.opacity = ''); });
            });
        }

        function renderLocShiftSplit(host, M) {
            const colors = ['#AD8A3E', '#4A6FA0', '#4C7A5D', '#7D6296'];
            function miniBars(entries, total) {
                return entries.map(([k, v], i) => {
                    const pct = Math.round(v / total * 100);
                    return `<div class="rank-row"><div class="rank-name" style="width:76px;">${k}</div><div class="rank-track"><div class="rank-fill" style="width:${pct}%; background:${colors[i % colors.length]}"></div></div><div class="rank-val">${v}</div></div>`;
                }).join('');
            }
            const locs = Object.entries(M.byLoc), shifts = Object.entries(M.byShift);
            const totalLoc = locs.reduce((a, [, v]) => a + v, 0) || 1, totalShift = shifts.reduce((a, [, v]) => a + v, 0) || 1;
            host.innerHTML = `<div style="display:grid; grid-template-columns:1fr 1fr; gap:18px;">
                <div><div class="eyebrow" style="margin-bottom:10px;">By Location</div><div class="rank-list">${miniBars(locs, totalLoc)}</div></div>
                <div><div class="eyebrow" style="margin-bottom:10px;">By Shift</div><div class="rank-list">${miniBars(shifts, totalShift)}</div></div>
            </div>`;
        }

        function renderHoursScatter(host, M) {
            const pts = Object.values(M.perEmp).filter(pe => pe.hrsCount > 0);
            if (!pts.length) { host.innerHTML = '<div class="mtile-empty">No attendance data to plot for this range.</div>'; return; }
            const w = 620, h = 210, pad = 34;
            const xs = pts.map(p => p.hrsSum / p.hrsCount);
            const xMax = Math.max(...xs, 9) * 1.05, xMin = Math.min(...xs, 6) * 0.95;
            const yMax = Math.max(...pts.map(p => p.otSum), 1) * 1.15;
            const deptColor = {}; DEPTS.forEach((d, i) => deptColor[d] = ['#AD8A3E', '#4A6FA0', '#4C7A5D', '#7D6296', '#BE6A34', '#9C3B3B', '#8F887A', '#B98A2E'][i % 8]);
            let dots = '';
            pts.forEach(p => {
                const avgH = p.hrsSum / p.hrsCount;
                const x = pad + ((avgH - xMin) / ((xMax - xMin) || 1)) * (w - pad * 2);
                const y = h - pad - (p.otSum / (yMax || 1)) * (h - pad * 2);
                const r = 4 + Math.min(6, p.leave * 1.4);
                dots += `<circle class="scatter-dot" data-name="${p.emp.name}" data-dept="${p.emp.dept}" data-hrs="${avgH.toFixed(1)}" data-ot="${p.otSum.toFixed(1)}" data-leave="${p.leave}" cx="${x.toFixed(1)}" cy="${y.toFixed(1)}" r="${r}" fill="${deptColor[p.emp.dept]}" fill-opacity="0.65" stroke="${deptColor[p.emp.dept]}" stroke-width="1"/>`;
            });
            host.innerHTML = `<svg width="100%" height="${h}" viewBox="0 0 ${w} ${h}">
                <line x1="${pad}" y1="${h - pad}" x2="${w - pad}" y2="${h - pad}" stroke="var(--border)"/>
                <line x1="${pad}" y1="${pad}" x2="${pad}" y2="${h - pad}" stroke="var(--border)"/>
                <text x="${w / 2}" y="${h - 6}" text-anchor="middle" font-size="9" fill="var(--ink-faint)">Avg hours / day →</text>
                <text x="12" y="${h / 2}" text-anchor="middle" font-size="9" fill="var(--ink-faint)" transform="rotate(-90 12 ${h / 2})">Overtime hrs →</text>
                ${dots}
            </svg>
            <div class="mtile-foot"><span>Bubble size = leave days taken</span><span>${pts.length} employees plotted</span></div>`;
            host.querySelectorAll('.scatter-dot').forEach(dot => {
                dot.addEventListener('mousemove', e => showTip(e, `<div class="tt-title">${dot.dataset.name}</div><div class="tt-row"><span>Dept</span><b>${dot.dataset.dept}</b></div><div class="tt-row"><span>Avg hrs/day</span><b>${dot.dataset.hrs}h</b></div><div class="tt-row"><span>Overtime</span><b>${dot.dataset.ot}h</b></div><div class="tt-row"><span>Leave days</span><b>${dot.dataset.leave}</b></div>`));
                dot.addEventListener('mouseleave', hideTip);
            });
        }

        function renderPunctualityFunnel(host, M) {
            const rows = [
                { label: 'On Time', v: M.punct.onTime, c: '#4C7A5D' },
                { label: 'Late <20m', v: M.punct.lateSoft, c: '#B98A2E' },
                { label: 'Late 20m+', v: M.punct.lateHard, c: '#BE6A34' },
                { label: 'Absent', v: M.absent, c: '#9C3B3B' },
            ];
            const max = Math.max(...rows.map(r => r.v), 1);
            host.innerHTML = `<div class="rank-list">${rows.map(r => `
                <div class="rank-row">
                    <div class="rank-name" style="width:96px;">${r.label}</div>
                    <div class="rank-track"><div class="rank-fill" style="width:${(r.v / max * 100).toFixed(1)}%; background:${r.c};"></div></div>
                    <div class="rank-val">${r.v}</div>
                </div>`).join('')}</div>`;
        }

        function renderRankedLists(host, M) {
            const arr = Object.values(M.perEmp);
            const topOT = [...arr].sort((a, b) => b.otSum - a.otSum).filter(p => p.otSum > 0).slice(0, 5);
            const topAbs = [...arr].sort((a, b) => b.absent - a.absent).filter(p => p.absent > 0).slice(0, 5);
            function list(entries, key, unit, color) {
                if (!entries.length) return `<div class="mtile-empty" style="padding:10px 0;">Nothing to flag</div>`;
                const max = Math.max(...entries.map(e => e[key]), 1);
                return entries.map((e, i) => `
                    <div class="rank-row">
                        <div class="rank-n">${i + 1}</div>
                        <div class="rank-name" title="${e.emp.name}">${e.emp.name}</div>
                        <div class="rank-track"><div class="rank-fill" style="width:${(e[key] / max * 100).toFixed(1)}%; background:${color};"></div></div>
                        <div class="rank-val">${e[key]}${unit}</div>
                    </div>`).join('');
            }
            host.innerHTML = `<div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                <div><div class="eyebrow" style="margin-bottom:10px;">Most Overtime</div>${list(topOT, 'otSum', 'h', '#BE6A34')}</div>
                <div><div class="eyebrow" style="margin-bottom:10px;">Highest Absenteeism</div>${list(topAbs, 'absent', 'd', '#9C3B3B')}</div>
            </div>`;
        }

        function renderDayOfWeek(host, M) {
            const order = [1, 2, 3, 4, 5, 6, 0];
            const names = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            const rows = order.map((dow, i) => {
                const a = M.dowAgg[dow];
                const pct = a.total ? Math.round(a.present / a.total * 100) : null;
                return { name: names[i], pct };
            });
            host.innerHTML = `<div class="rank-list">${rows.map(r => `
                <div class="rank-row">
                    <div class="rank-name" style="width:40px;">${r.name}</div>
                    <div class="rank-track"><div class="rank-fill" style="width:${r.pct === null ? 0 : r.pct}%; background:${r.pct === null ? 'var(--border)' : (r.pct < 70 ? '#9C3B3B' : '#AD8A3E')};"></div></div>
                    <div class="rank-val">${r.pct === null ? '—' : r.pct + '%'}</div>
                </div>`).join('')}</div>
                <div class="mtile-foot"><span>Attendance rate by weekday, current range</span></div>`;
        }

        function renderManagerTable(host, M) {
            const rows = Object.entries(M.byManager).map(([name, s]) => ({
                name, headcount: s.headcount,
                pct: s.total ? Math.round((s.present + s.late + s.wfh) / s.total * 100) : 0,
                pending: s.pending
            })).sort((a, b) => b.headcount - a.headcount);
            host.innerHTML = `<table class="mtable">
                <thead><tr><th>Manager</th><th>Team</th><th>Attendance</th><th>Pending</th></tr></thead>
                <tbody>${rows.map(r => {
                    const color = r.pct >= 85 ? 'var(--present)' : r.pct >= 70 ? 'var(--late)' : 'var(--absent)';
                    const bg = r.pct >= 85 ? 'var(--present-bg)' : r.pct >= 70 ? 'var(--late-bg)' : 'var(--absent-bg)';
                    return `<tr><td>${r.name}</td><td class="mono">${r.headcount}</td><td><span class="pct-pill" style="color:${color}; background:${bg};">${r.pct}%</span></td><td class="mono">${r.pending}</td></tr>`;
                }).join('')}</tbody>
            </table>`;
        }

        function renderPayrollCost(host, M) {
            const buckets = [];
            for (let i = 0; i < M.trend.length; i += 7) {
                const chunk = M.trend.slice(i, i + 7);
                const hrs = chunk.reduce((a, s) => a + s.hrsSum, 0);
                buckets.push({ label: 'W' + (buckets.length + 1), cost: hrs * 380 });
            }
            const max = Math.max(...buckets.map(b => b.cost), 1);
            host.innerHTML = `
                <div style="font-family:'Fraunces',serif; font-size:24px; font-weight:500; margin-bottom:10px;">₹${Math.round(M.payrollCost).toLocaleString('en-IN')}</div>
                <div style="display:flex; align-items:flex-end; gap:6px; height:78px;">
                    ${buckets.map(b => `<div title="${b.label}: ₹${Math.round(b.cost).toLocaleString('en-IN')}" style="flex:1; height:${Math.max(4, (b.cost / max * 78))}px; background:linear-gradient(180deg,#C9A227,var(--gold)); border-radius:4px 4px 0 0;"></div>`).join('')}
                </div>
                <div class="mtile-foot"><span>Modeled at ₹380/hr blended rate</span><span>${buckets.length} week${buckets.length === 1 ? '' : 's'}</span></div>`;
        }

        /* ---------------- bento registry ---------------- */
        const METRIC_TILES = [
            { id: 'trend', span: 8, title: 'Attendance Trend', sub: 'Daily composition across the selected range', glow: 'rgba(173,138,62,0.3)', render: renderTrendArea },
            { id: 'health', span: 4, title: 'Attendance Health', sub: 'Share of days by status', glow: 'rgba(76,122,93,0.3)', render: renderHealthDonut },
            { id: 'heatmap', span: 7, title: 'Department Heatmap', sub: 'Daily attendance % by department', glow: 'rgba(173,138,62,0.25)', render: renderDeptHeatmap },
            { id: 'leave', span: 5, title: 'Leave Composition', sub: 'Breakdown of leave types taken', glow: 'rgba(173,138,62,0.25)', render: renderLeaveBreakdown },
            { id: 'radar', span: 6, title: 'Department Scorecard', sub: 'Five-axis comparison — hover a name to isolate it', glow: 'rgba(125,98,150,0.28)', render: renderDeptRadar },
            { id: 'locshift', span: 6, title: 'Location & Shift Mix', sub: 'Headcount distribution', glow: 'rgba(74,111,160,0.25)', render: renderLocShiftSplit },
            { id: 'scatter', span: 7, title: 'Hours vs Overtime', sub: 'Per employee, bubble sized by leave days', glow: 'rgba(190,106,52,0.25)', render: renderHoursScatter },
            { id: 'funnel', span: 5, title: 'Punctuality Funnel', sub: 'On time, late and absent split', glow: 'rgba(156,59,59,0.22)', render: renderPunctualityFunnel },
            { id: 'ranked', span: 6, title: 'Outliers to Watch', sub: 'Top overtime and absenteeism this range', glow: 'rgba(173,138,62,0.25)', render: renderRankedLists },
            { id: 'dow', span: 6, title: 'Weekday Pattern', sub: 'Attendance rate by day of week', glow: 'rgba(76,122,93,0.22)', render: renderDayOfWeek },
            { id: 'managers', span: 8, title: 'Manager Span of Control', sub: 'Team size and attendance by manager', glow: 'rgba(173,138,62,0.22)', render: renderManagerTable },
            { id: 'payroll', span: 4, title: 'Est. Payroll Cost', sub: 'Modeled from logged hours, weekly', glow: 'rgba(173,138,62,0.3)', render: renderPayrollCost },
        ];

        function renderMetricsBento(M, days, emps) {
            const bento = document.getElementById('metricsBento');
            bento.innerHTML = METRIC_TILES.map(t => `
                <div class="mtile glass tilt-card span-${t.span}" style="--glow:${t.glow}">
                    <div class="glass-edge"></div>
                    <div class="mtile-head"><div><h4>${t.title}</h4><div class="sub">${t.sub}</div></div></div>
                    <div class="mtile-body" id="body-${t.id}"></div>
                </div>`).join('');
            METRIC_TILES.forEach(t => {
                const host = document.getElementById('body-' + t.id);
                try { t.render(host, M, days, emps); }
                catch (err) { host.innerHTML = '<div class="mtile-empty">No data for the current filters.</div>'; console.error('[metric:' + t.id + ']', err); }
            });
        }

        /* =====================================================================
           REFRESH ENGINE
        ===================================================================== */
        function refreshAnalytics() {
            const days = getSelectedDays();
            const emps = getFilteredEmployees();
            const M = computeMetrics(days, emps);
            const prevDays = getPreviousDays(days);
            const PM = prevDays ? computeMetrics(prevDays, emps) : null;
            
            const labelMap = { today: 'Today', week: 'This Week', month: 'This Month', custom: 'Custom Range' };
            const subLabel = document.getElementById('analyticsRangeLabel');
            if (subLabel) {
                subLabel.textContent = `Showing ${labelMap[activeRange]} · ${currentDate.toLocaleString('default', { month: 'long', year: 'numeric' })} · ${emps.length} of ${employees.length} employees`;
            }
            renderKpiStrip(M, PM, days);
            renderMetricsBento(M, days, emps);
            attachTiltAll('.tilt-card, .mtile, .kpi-tile');
            attachGlassGlow('.glass');
        }

        /* =====================================================================
           MATRIX RENDERING
        ===================================================================== */
        const WD = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        function renderMatrix() {
            const table = document.getElementById('matrixTable');
            const emps = getFilteredEmployees();

            let thead = '<thead><tr>';
            thead += `<th class="col-check stick stick-head"><input type="checkbox" id="selectAllCheckbox"></th>`;
            thead += `<th class="col-emp stick stick-head" style="text-align:left;">Employee</th>`;
            thead += `<th class="col-dept stick stick-head" style="text-align:left;">Department</th>`;
            
            // Loop over carbon datelist from Laravel to map actual dates
            laravelDateList.forEach(dStr => {
                const d = new Date(dStr);
                const day = d.getDate();
                const dow = d.getDay();
                const isWeekend = (dow === 0 || dow === 6);
                const isHoliday = !!HOLIDAYS[dStr];
                const isToday = (d.getFullYear() === today.getFullYear() && d.getMonth() === today.getMonth() && day === today.getDate());
                
                let cls = '';
                if (isWeekend) cls += ' weekend';
                if (isHoliday) cls += ' holiday-col';
                if (isToday) cls += ' today-col';
                thead += `<th class="${cls}"><div class="daynum">${day}</div><div class="wk">${WD[dow]}${isHoliday ? ' • Hol' : ''}</div></th>`;
            });
            thead += '</tr></thead>';

            let tbody = '<tbody>';
            emps.forEach(emp => {
                tbody += `<tr class="emp-row" data-emp-id="${emp.id}">`;
                tbody += `<td class="col-check stick"><input type="checkbox" class="row-check" data-emp="${emp.id}"></td>`;
                tbody += `<td class="col-emp stick"><div class="emp-cell"><div class="avatar">${emp.initials}</div><div><div class="emp-name">${emp.name}</div><div class="emp-sub">${emp.employee_id}</div></div></div></td>`;
                tbody += `<td class="col-dept stick"><span class="dept-pill"><span class="dsw"></span>${emp.dept}</span></td>`;
                
                laravelDateList.forEach(dStr => {
                    const c = (attendance[emp.id] || []).find(item => item.date === dStr) || { status: 'absent', label: 'Absent', date: dStr, day: parseInt(dStr.split('-')[2]) };
                    const d = new Date(dStr);
                    const isToday = (d.getFullYear() === today.getFullYear() && d.getMonth() === today.getMonth() && d.getDate() === today.getDate());
                    const ov = c.override ? ' override' : '';
                    const tm = isToday ? ' today-marker' : '';
                    
                    tbody += `<td class="dcell ${c.status}${ov}${tm}" data-emp="${emp.id}" data-date="${dStr}">
                        <div class="status-label">${c.label}</div>
                        ${c.hrs ? `<div class="hrs">${c.hrs}</div>` : ''}
                    </td>`;
                });
                tbody += '</tr>';
            });
            tbody += '</tbody>';

            table.innerHTML = thead + tbody;
            
            // Wire checkbox selectAll
            const selectAll = document.getElementById('selectAllCheckbox');
            if (selectAll) {
                selectAll.addEventListener('change', () => {
                    document.querySelectorAll('.row-check').forEach(cb => {
                        cb.checked = selectAll.checked;
                    });
                    updateBulkBar();
                });
            }
            
            attachMatrixEvents();
        }

        function attachMatrixEvents() {
            document.querySelectorAll('.dcell').forEach(cell => {
                cell.addEventListener('mouseenter', (e) => showPopover(e, cell));
                cell.addEventListener('mousemove', (e) => positionPopover(e));
                cell.addEventListener('mouseleave', hidePopover);
                cell.addEventListener('click', () => openDrawer(cell));
            });
            document.querySelectorAll('.row-check').forEach(cb => {
                cb.addEventListener('change', updateBulkBar);
            });
        }

        /* Matrix hover popover */
        const popover = document.getElementById('popover');
        function showPopover(e, cell) {
            const empId = cell.dataset.emp, dateStr = cell.dataset.date;
            const emp = employees.find(x => x.id === empId);
            const c = (attendance[empId] || []).find(item => item.date === dateStr) || { status: 'absent', label: 'Absent', date: dateStr };
            
            const displayTimeIn = c.inT || '—';
            const displayTimeOut = c.outT || '—';
            const displayHours = c.hrs || '—';
            const displayOvertime = c.ot || '0h';
            
            popover.innerHTML = `
                <div class="ph-title">${emp.name} · ${dateStr}</div>
                <div class="prow"><span>Punch In</span><b>${displayTimeIn}</b></div>
                <div class="prow"><span>Punch Out</span><b>${displayTimeOut}</b></div>
                <div class="prow"><span>Working Hours</span><b>${displayHours}</b></div>
                <div class="prow"><span>Break</span><b>${c.hrs ? '45m' : '—'}</b></div>
                <div class="prow"><span>Late Minutes</span><b>${c.lateMin || 0}m</b></div>
                <div class="prow"><span>Overtime</span><b>${displayOvertime}</b></div>
                <div class="prow"><span>Shift</span><b>${emp.shift}</b></div>
                <div class="prow"><span>Approval</span><b>${c.status === 'late' || c.status === 'wfh' ? 'Pending' : 'Approved'}</b></div>
            `;
            popover.style.display = 'block';
            positionPopover(e);
        }
        
        function positionPopover(e) {
            const pad = 14;
            let x = e.clientX + pad, y = e.clientY + pad;
            if (x + 270 > window.innerWidth) x = e.clientX - 270 - pad;
            if (y + 220 > window.innerHeight) y = e.clientY - 220 - pad;
            popover.style.left = x + 'px';
            popover.style.top = y + 'px';
        }
        function hidePopover() { popover.style.display = 'none'; }

        /* Drawer */
        const drawer = document.getElementById('drawer');
        const overlay = document.getElementById('overlay');
        function openDrawer(cell) {
            const empId = cell.dataset.emp, dateStr = cell.dataset.date;
            const emp = employees.find(x => x.id === empId);
            const c = (attendance[empId] || []).find(item => item.date === dateStr) || { status: 'absent', label: 'Absent', date: dateStr };
            
            // Set initial client-side placeholders
            document.getElementById('drawerName').textContent = emp.name;
            document.getElementById('drawerSub').textContent = `${emp.id} · ${emp.dept} · ${emp.desig} · ${dateStr}`;
            
            const badge = document.getElementById('drawerBadge');
            badge.textContent = c.label.toUpperCase();
            badge.style.background = `var(--${c.status}-bg)`;
            badge.style.color = `var(--${c.status})`;
            
            document.getElementById('dIn').textContent = c.inT || '—';
            document.getElementById('dOut').textContent = c.outT || '—';
            document.getElementById('dHrs').textContent = c.hrs || '—';
            document.getElementById('dBreak').textContent = c.hrs ? '45m' : '—';
            document.getElementById('dLate').textContent = (c.lateMin || 0) + 'm';
            document.getElementById('dOt').textContent = c.ot || '0h';
            
            document.getElementById('dShift').textContent = emp.shift;
            document.getElementById('dApproval').textContent = c.status === 'late' || c.status === 'wfh' ? 'Pending Review' : 'Approved';
            document.getElementById('dNotes').value = c.notes || '';
            document.getElementById('dGeo').textContent = '—';
            document.getElementById('dSource').textContent = '—';
            document.getElementById('dAudit').innerHTML = '<div class="t">Loading database audit logs...</div>';
            
            // Hydrate drawer via live Laravel dossier API
            fetch(`/admin/attendance-ledger/dossier?employee_id=${empId}&date=${dateStr}`)
                .then(r => r.json())
                .then(data => {
                    if (data.error) return;

                    // Hydrate Manager and Subtext
                    document.getElementById('dManager').textContent = data.employee.manager || 'None';
                    document.getElementById('drawerSub').textContent = `${data.employee.employee_id} · ${data.employee.department} · ${data.employee.designation}`;

                    // Scheduled Shift details
                    document.getElementById('dShift').textContent = data.resolved.timings.start_time + ' – ' + data.resolved.timings.end_time;
                    document.getElementById('dGrace').textContent = data.resolved.timings.grace_minutes + ' minutes';
                    
                    // Location and source
                    document.getElementById('dGeo').textContent = data.resolved.check_in_location || '—';
                    document.getElementById('dSource').textContent = data.resolved.check_in_device || '—';

                    // Audit logs
                    const auditDiv = document.getElementById('dAudit');
                    if (data.audit && data.audit.length > 0) {
                        auditDiv.innerHTML = data.audit.map(log => `
                            <div class="audit-item">
                                <div class="dot"></div>
                                <div>
                                    <div><strong>${log.action}</strong> by ${log.performed_by}</div>
                                    <div style="font-size: 11px; color: var(--ink-soft);">${log.reason || 'No justification provided'}</div>
                                    <div class="t">${log.timestamp}</div>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        auditDiv.innerHTML = '<div class="t">No audit logs available for this day.</div>';
                    }

                    // Payroll details
                    if (data.payroll_impact) {
                        document.getElementById('dPayrollCycle').textContent = data.payroll_impact.period;
                        document.getElementById('dPayrollRate').textContent = isPayrollAuthorized ? '₹' + parseFloat(data.payroll_impact.daily_rate).toLocaleString('en-IN') : 'Restricted';
                        document.getElementById('dPayrollFactor').textContent = data.payroll_impact.deduction_factor;
                        document.getElementById('dPayrollDeduction').textContent = isPayrollAuthorized ? '₹' + parseFloat(data.payroll_impact.deducted_amount).toLocaleString('en-IN') : 'Restricted';
                        document.getElementById('dPayrollVersion').textContent = 'v' + data.payroll_impact.calculation_version;
                        document.getElementById('dPayrollLock').textContent = data.payroll_impact.locked ? 'Locked (Immutable)' : 'Unlocked';
                    }

                    // Leave context
                    const leaveSec = document.getElementById('drawerLeaveSec');
                    if (data.leave_context) {
                        leaveSec.style.display = 'block';
                        document.getElementById('dLeaveType').textContent = data.leave_context.leave_type_label + (data.leave_context.is_half_day ? ' (Half Day)' : ' (Full Day)');
                        document.getElementById('dLeaveStatus').textContent = data.leave_context.status.toUpperCase();
                        document.getElementById('dLeaveAffected').textContent = data.leave_context.affected_attendance ? 'Yes' : 'No';
                    } else {
                        leaveSec.style.display = 'none';
                    }

                    // Manual Override context
                    const overrideSec = document.getElementById('drawerOverrideSec');
                    if (data.history && Object.keys(data.history).length > 0) {
                        overrideSec.style.display = 'block';
                        document.getElementById('dOverrideStatus').textContent = data.history.status.toUpperCase();
                        document.getElementById('dOverrideOriginal').textContent = data.history.original_status.toUpperCase();
                        document.getElementById('dOverrideReason').textContent = data.history.reason;
                        document.getElementById('dOverrideBy').textContent = data.history.changed_by;
                        document.getElementById('dOverrideTime').textContent = data.history.timestamp;
                    } else {
                        overrideSec.style.display = 'none';
                    }

                    // Prepopulate override inputs
                    document.getElementById('correctionStatus').value = c.status === 'paidleave' ? 'planned' : (c.status === 'unplanned' ? 'upa' : (c.status === 'weekoff' ? 'off' : c.status));
                    document.getElementById('correctionClass').value = data.resolved.classification;
                    document.getElementById('correctionReason').value = '';

                    // Save context metadata on form
                    const form = document.getElementById('overrideForm');
                    form.dataset.empId = empId;
                    form.dataset.date = dateStr;
                });

            // Hide form by default
            document.getElementById('drawerCorrectionFormSec').style.display = 'none';

            drawer.classList.add('open');
            overlay.style.display = 'block';
        }

        document.getElementById('drawerClose').addEventListener('click', closeDrawer);
        overlay.addEventListener('click', closeDrawer);
        function closeDrawer() {
            drawer.classList.remove('open');
            overlay.style.display = 'none';
            document.getElementById('bulkModal').style.display = 'none';
        }

        /* Bulk bar actions */
        const bulkBar = document.getElementById('bulkBar');
        function updateBulkBar() {
            const checked = document.querySelectorAll('.row-check:checked');
            document.getElementById('bulkCount').textContent = checked.length + ' selected';
            bulkBar.classList.toggle('show', checked.length > 0);
        }

        document.getElementById('bulkClose').addEventListener('click', () => {
            document.querySelectorAll('.row-check:checked').forEach(cb => cb.checked = false);
            const selectAll = document.getElementById('selectAllCheckbox');
            if (selectAll) selectAll.checked = false;
            updateBulkBar();
        });

        document.getElementById('bulkToggleBtn').addEventListener('click', () => {
            const checked = document.querySelectorAll('.row-check:checked');
            if (!checked.length) {
                alert('Please select at least one employee row first.');
                return;
            }
            openBulkOverrideModal();
        });

        // Wire bulk bar action buttons
        document.querySelectorAll('.bulk-bar .bactions button').forEach(btn => {
            btn.addEventListener('click', () => {
                const action = btn.dataset.b;
                const checked = document.querySelectorAll('.row-check:checked');
                if (!checked.length) {
                    alert('Please select at least one employee row first.');
                    return;
                }
                
                if (['present', 'absent', 'planned', 'off', 'wfh'].includes(action)) {
                    openBulkOverrideModal(action);
                } else if (action === 'override') {
                    openBulkOverrideModal();
                } else {
                    alert(`${btn.textContent} bulk action is not configured under AMS daily ledger operations. Please contact your system administrator.`);
                }
            });
        });

        const bulkModal = document.getElementById('bulkModal');
        function openBulkOverrideModal(preselectedStatus) {
            const checked = document.querySelectorAll('.row-check:checked');
            document.getElementById('bulkModalCount').textContent = checked.length + ' employee(s) selected';
            
            // Reset modal values
            document.getElementById('bulkPreviewAlert').style.display = 'none';
            document.getElementById('btnSubmitBulk').disabled = true;
            document.getElementById('bulkReason').value = '';
            
            if (preselectedStatus) {
                document.getElementById('bulkStatus').value = preselectedStatus;
            }

            bulkModal.style.display = 'block';
            overlay.style.display = 'block';
        }

        document.getElementById('bulkModalClose').addEventListener('click', () => {
            bulkModal.style.display = 'none';
            overlay.style.display = 'none';
        });

        document.getElementById('bulkDateMode').addEventListener('change', e => {
            const isRange = e.target.value === 'range';
            document.getElementById('bulkSingleDateSection').style.display = isRange ? 'none' : '';
            document.getElementById('bulkRangeSection').style.display = isRange ? 'flex' : 'none';
        });

        /* Wire dropdown inputs and filter datasets */
        function populateFilterDropdowns() {
            const mfLoc = document.getElementById('mfLoc');
            const mfShift = document.getElementById('mfShift');
            const mfManager = document.getElementById('mfManager');
            const mfDept = document.getElementById('mfDeptChips');

            const lfDept = document.getElementById('lfDept');
            const lfDesig = document.getElementById('lfDesig');
            const lfShift = document.getElementById('lfShift');
            const lfLoc = document.getElementById('lfLocation');
            const lfMgr = document.getElementById('lfManager');

            // Populate Locations
            LOCS.forEach(l => {
                mfLoc.insertAdjacentHTML('beforeend', `<option value="${l}">${l}</option>`);
                lfLoc.insertAdjacentHTML('beforeend', `<option value="${l}">${l}</option>`);
            });

            // Populate Shifts
            SHIFTS.forEach(s => {
                mfShift.insertAdjacentHTML('beforeend', `<option value="${s}">${s} Shift</option>`);
                lfShift.insertAdjacentHTML('beforeend', `<option value="${s}">${s} Shift</option>`);
            });

            // Populate Managers
            MANAGERS.forEach(m => {
                mfManager.insertAdjacentHTML('beforeend', `<option value="${m}">${m}</option>`);
                lfMgr.insertAdjacentHTML('beforeend', `<option value="${m}">${m}</option>`);
            });

            // Populate Designations
            DESIGNS.forEach(d => {
                lfDesig.insertAdjacentHTML('beforeend', `<option value="${d}">${d}</option>`);
            });

            // Populate Departments
            DEPTS.forEach(d => {
                lfDept.insertAdjacentHTML('beforeend', `<option value="${d}">${d}</option>`);
            });

            // Render chips
            mfDept.innerHTML = DEPTS.map(d => `<button type="button" class="chip" data-dept="${d}">${d}</button>`).join('');
        }
        populateFilterDropdowns();

        // Sync filter logic between metrics bar and lower filter bar
        function syncFiltersAndRender(key, value) {
            mFilters[key] = value;
            refreshAnalytics();
            renderMatrix();
        }

        // Metrics filter listeners
        document.getElementById('mfDeptChips').addEventListener('click', e => {
            const btn = e.target.closest('.chip'); if (!btn) return;
            btn.classList.toggle('active');
            const d = btn.dataset.dept;
            if (mFilters.dept.has(d)) mFilters.dept.delete(d); else mFilters.dept.add(d);
            
            // Sync with bottom select
            const lfDept = document.getElementById('lfDept');
            if (mFilters.dept.size === 1) {
                lfDept.value = [...mFilters.dept][0];
            } else {
                lfDept.value = 'all';
            }

            refreshAnalytics();
            renderMatrix();
        });

        ['mfLoc', 'mfShift', 'mfManager'].forEach(id => {
            document.getElementById(id).addEventListener('change', e => {
                const key = id === 'mfLoc' ? 'loc' : (id === 'mfShift' ? 'shift' : 'manager');
                
                // Sync with lower select
                const targetId = id === 'mfLoc' ? 'lfLocation' : (id === 'mfShift' ? 'lfShift' : 'lfManager');
                const target = document.getElementById(targetId);
                if (target) target.value = e.target.value;

                syncFiltersAndRender(key, e.target.value);
            });
        });

        document.getElementById('mfSearch').addEventListener('input', e => {
            document.getElementById('lfSearch').value = e.target.value;
            syncFiltersAndRender('search', e.target.value);
        });

        // Lower filters listeners
        document.getElementById('lfSearch').addEventListener('input', e => {
            document.getElementById('mfSearch').value = e.target.value;
            syncFiltersAndRender('search', e.target.value);
        });

        document.getElementById('lfDept').addEventListener('change', e => {
            mFilters.dept.clear();
            document.querySelectorAll('#mfDeptChips .chip').forEach(c => {
                if (c.dataset.dept === e.target.value) {
                    c.classList.add('active');
                    mFilters.dept.add(c.dataset.dept);
                } else {
                    c.classList.remove('active');
                }
            });
            refreshAnalytics();
            renderMatrix();
        });

        ['lfLocation', 'lfShift', 'lfManager'].forEach(id => {
            document.getElementById(id).addEventListener('change', e => {
                const key = id === 'lfLocation' ? 'loc' : (id === 'lfShift' ? 'shift' : 'manager');
                const topId = id === 'lfLocation' ? 'mfLoc' : (id === 'lfShift' ? 'mfShift' : 'mfManager');
                const topTarget = document.getElementById(topId);
                if (topTarget) topTarget.value = e.target.value;
                
                syncFiltersAndRender(key, e.target.value);
            });
        });

        ['lfDesig', 'lfEmploymentType', 'lfWorkMode'].forEach(id => {
            document.getElementById(id).addEventListener('change', e => {
                const key = id === 'lfDesig' ? 'desig' : (id === 'lfEmploymentType' ? 'employment_type' : 'work_mode');
                syncFiltersAndRender(key, e.target.value);
            });
        });

        document.getElementById('lfStatus').addEventListener('change', e => {
            // Map lower status filters into filter chipset logic
            const status = e.target.value;
            document.querySelectorAll('.chips-row .chip:not(.reset)').forEach(c => {
                if (c.dataset.chip === status || (status === 'leave' && c.dataset.chip === 'leave')) {
                    c.classList.add('active');
                } else {
                    c.classList.remove('active');
                }
            });
            mFilters.onlyStatus = status === 'all' ? null : status;
            refreshAnalytics();
            renderMatrix();
        });

        // Filter chips listeners
        document.querySelectorAll('.chips-row .chip:not(.reset)').forEach(chip => {
            chip.addEventListener('click', () => {
                const wasActive = chip.classList.contains('active');
                document.querySelectorAll('.chips-row .chip:not(.reset)').forEach(c => c.classList.remove('active'));
                
                if (!wasActive) {
                    chip.classList.add('active');
                    mFilters.onlyStatus = chip.dataset.chip;
                    document.getElementById('lfStatus').value = (chip.dataset.chip === 'paidleave' || chip.dataset.chip === 'unplanned') ? 'leave' : chip.dataset.chip;
                } else {
                    mFilters.onlyStatus = null;
                    document.getElementById('lfStatus').value = 'all';
                }
                refreshAnalytics();
                renderMatrix();
            });
        });

        // Reset all filters
        const resetHandler = () => {
            mFilters = { dept: new Set(), loc: 'all', shift: 'all', manager: 'all', desig: 'all', employment_type: 'all', entity: 'all', work_mode: 'all', search: '', onlyStatus: null };
            document.querySelectorAll('#mfDeptChips .chip').forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.chips-row .chip').forEach(c => c.classList.remove('active'));
            
            document.getElementById('mfLoc').value = 'all';
            document.getElementById('mfShift').value = 'all';
            document.getElementById('mfManager').value = 'all';
            document.getElementById('mfSearch').value = '';

            document.getElementById('lfDept').value = 'all';
            document.getElementById('lfDesig').value = 'all';
            document.getElementById('lfShift').value = 'all';
            document.getElementById('lfLocation').value = 'all';
            document.getElementById('lfManager').value = 'all';
            document.getElementById('lfWorkMode').value = 'all';
            document.getElementById('lfStatus').value = 'all';
            document.getElementById('lfSearch').value = '';

            refreshAnalytics();
            renderMatrix();
        };
        document.getElementById('mfReset').addEventListener('click', resetHandler);
        document.getElementById('resetFilters').addEventListener('click', resetHandler);

        /* Date range seg controls redirect logic */
        document.getElementById('rangeSeg').addEventListener('click', e => {
            const btn = e.target.closest('button'); if (!btn) return;
            activeRange = btn.dataset.range;
            
            const start = document.getElementById('customStart').value;
            const end = document.getElementById('customEnd').value;
            const month = "{{ $carbonMonth->format('Y-m') }}";
            window.location.href = `/admin/attendance-ledger?range=${activeRange}&month=${month}&start_date=${start}&end_date=${end}`;
        });

        document.getElementById('customStart').addEventListener('change', () => {
            if (activeRange === 'custom') {
                const start = document.getElementById('customStart').value;
                const end = document.getElementById('customEnd').value;
                window.location.href = `/admin/attendance-ledger?range=custom&month={{ $carbonMonth->format('Y-m') }}&start_date=${start}&end_date=${end}`;
            }
        });
        
        document.getElementById('customEnd').addEventListener('change', () => {
            if (activeRange === 'custom') {
                const start = document.getElementById('customStart').value;
                const end = document.getElementById('customEnd').value;
                window.location.href = `/admin/attendance-ledger?range=custom&month={{ $carbonMonth->format('Y-m') }}&start_date=${start}&end_date=${end}`;
            }
        });

        // Month navigation toolbar redirect
        document.getElementById('prevMonth').addEventListener('click', () => {
            window.location.href = `/admin/attendance-ledger?range=${activeRange}&month={{ $carbonMonth->copy()->subMonth()->format('Y-m') }}`;
        });
        document.getElementById('nextMonth').addEventListener('click', () => {
            window.location.href = `/admin/attendance-ledger?range=${activeRange}&month={{ $carbonMonth->copy()->addMonth()->format('Y-m') }}`;
        });
        document.getElementById('todayBtn').addEventListener('click', () => {
            window.location.href = `/admin/attendance-ledger?range=month&month={{ today()->format('Y-m') }}`;
        });

        // Lock/Unlock indicator
        let locked = false;
        document.getElementById('lockBtn').addEventListener('click', function () {
            locked = !locked;
            this.classList.toggle('locked', locked);
        });

        // Toggle override form inside drawer
        const toggleForm = () => {
            const formSec = document.getElementById('drawerCorrectionFormSec');
            const isHidden = formSec.style.display === 'none';
            formSec.style.display = isHidden ? 'block' : 'none';
            if (isHidden) {
                document.querySelector('.drawer-body').scrollTo({
                    top: formSec.offsetTop,
                    behavior: 'smooth'
                });
            }
        };
        document.getElementById('btnEditAttendance').addEventListener('click', toggleForm);
        document.getElementById('btnOverrideAttendance').addEventListener('click', toggleForm);

        // Interactive controls handling
        document.getElementById('btnApproveAttendance').addEventListener('click', () => {
            const form = document.getElementById('overrideForm');
            const empId = form.dataset.empId;
            const date = form.dataset.date;
            
            fetch(`/admin/attendance-ledger/dossier?employee_id=${empId}&date=${date}`)
                .then(r => r.json())
                .then(data => {
                    if (data.leave_context && data.leave_context.status === 'pending') {
                        if (confirm(`Approve pending leave request (${data.leave_context.leave_type_label}) for this day?`)) {
                            fetch(`/leaves/${data.leave_context.leave_id}/approve`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                    'Accept': 'application/json'
                                }
                            })
                            .then(r => r.json())
                            .then(res => {
                                if (res.error) alert(res.error);
                                else {
                                    alert(res.message || 'Approved successfully.');
                                    window.location.reload();
                                }
                            });
                        }
                    } else {
                        alert('No pending leave request to approve for this date.');
                    }
                });
        });

        document.getElementById('btnRejectAttendance').addEventListener('click', () => {
            const form = document.getElementById('overrideForm');
            const empId = form.dataset.empId;
            const date = form.dataset.date;

            fetch(`/admin/attendance-ledger/dossier?employee_id=${empId}&date=${date}`)
                .then(r => r.json())
                .then(data => {
                    if (data.leave_context && (data.leave_context.status === 'pending' || data.leave_context.status === 'approved')) {
                        const reason = prompt('Enter rejection reason (at least 5 characters):');
                        if (reason === null) return;
                        if (reason.length < 5) {
                            alert('Rejection reason must be at least 5 characters.');
                            return;
                        }
                        fetch(`/leaves/${data.leave_context.leave_id}/reject`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ rejection_reason: reason })
                        })
                        .then(r => r.json())
                        .then(res => {
                            if (res.error) alert(res.error);
                            else {
                                alert(res.message || 'Rejected successfully.');
                                window.location.reload();
                            }
                        });
                    } else {
                        alert('No pending or approved leave request to reject for this date.');
                    }
                });
        });

        document.getElementById('btnAssignLeave').addEventListener('click', () => {
            const form = document.getElementById('overrideForm');
            const empId = form.dataset.empId;
            const date = form.dataset.date;

            const type = prompt('Assign Leave: Enter leave type (planned, unplanned, complimentary):', 'planned');
            if (!type) return;
            if (type !== 'planned' && type !== 'unplanned' && type !== 'complimentary') {
                alert('Invalid leave type. Must be planned, unplanned, or complimentary.');
                return;
            }

            const duration = prompt('Enter duration (full_day or half_day):', 'full_day');
            if (!duration) return;
            if (duration !== 'full_day' && duration !== 'half_day') {
                alert('Invalid duration. Must be either full_day or half_day.');
                return;
            }

            const reason = prompt('Enter reason (at least 5 characters):');
            if (reason === null) return;
            if (reason.length < 5) {
                alert('Reason must be at least 5 characters.');
                return;
            }

            fetch('/admin/attendance-ledger/assign-leave', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    employee_id: empId,
                    date: date,
                    leave_type: type,
                    duration: duration,
                    reason: reason
                })
            })
            .then(r => r.json())
            .then(res => {
                if (res.error) alert(res.error);
                else {
                    alert(res.message || 'Leave assigned successfully.');
                    window.location.reload();
                }
            })
            .catch(err => {
                console.error('Error assigning leave:', err);
                alert('Error assigning leave.');
            });
        });

        document.getElementById('btnChangeShift').addEventListener('click', () => {
            const form = document.getElementById('overrideForm');
            const empId = form.dataset.empId;
            const date = form.dataset.date;

            const startTime = prompt('Change Shift: Enter shift start time (HH:MM:SS):', '09:30:00');
            if (!startTime) return;

            const endTime = prompt('Enter shift end time (HH:MM:SS):', '18:30:00');
            if (!endTime) return;

            const grace = prompt('Enter grace minutes:', '15');
            if (grace === null) return;
            const graceMinutes = parseInt(grace);
            if (isNaN(graceMinutes) || graceMinutes < 0) {
                alert('Grace minutes must be a positive integer.');
                return;
            }

            fetch('/admin/attendance-ledger/change-shift', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    employee_id: empId,
                    date: date,
                    shift_start_time: startTime,
                    shift_end_time: endTime,
                    grace_minutes: graceMinutes
                })
            })
            .then(r => r.json())
            .then(res => {
                if (res.error) alert(res.error);
                else {
                    alert(res.message || 'Shift changed successfully.');
                    window.location.reload();
                }
            })
            .catch(err => {
                console.error('Error changing shift:', err);
                alert('Error changing shift.');
            });
        });

        // Form override submission
        document.getElementById('overrideForm').addEventListener('submit', e => {
            e.preventDefault();
            const form = e.target;
            const empId = form.dataset.empId;
            const date = form.dataset.date;
            const status = document.getElementById('correctionStatus').value;
            const classification = document.getElementById('correctionClass').value;
            const reason = document.getElementById('correctionReason').value;

            fetch('/admin/attendance-ledger/override', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    employee_id: empId,
                    date: date,
                    status: status,
                    classification: classification,
                    override_reason: reason
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                } else {
                    alert(data.message || 'Override applied successfully.');
                    closeDrawer();
                    window.location.reload();
                }
            })
            .catch(err => {
                console.error('Error applying override:', err);
                alert('Error applying override.');
            });
        });

        // Bulk Preview Analysis
        document.getElementById('btnPreviewBulk').addEventListener('click', () => {
            const checked = document.querySelectorAll('.row-check:checked');
            const empIds = Array.from(checked).map(cb => cb.dataset.emp);
            const dateMode = document.getElementById('bulkDateMode').value;
            const date = document.getElementById('bulkSingleDate').value;
            const start = document.getElementById('bulkStartDate').value;
            const end = document.getElementById('bulkEndDate').value;
            const status = document.getElementById('bulkStatus').value;
            const classification = document.getElementById('bulkClass').value;
            const reason = document.getElementById('bulkReason').value;

            if (reason.length < 5) {
                alert('Justification note must be at least 5 characters long.');
                return;
            }

            fetch('/admin/attendance-ledger/bulk-preview', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    employee_ids: empIds,
                    date_mode: dateMode,
                    date: date,
                    start_date: start,
                    end_date: end,
                    status: status,
                    classification: classification,
                    override_reason: reason,
                    working_days_only: document.getElementById('bulkWorkingDays').checked,
                    skip_leaves: document.getElementById('bulkSkipLeaves').checked,
                    skip_overrides: document.getElementById('bulkSkipOverrides').checked,
                    conflict_handling: 'replace'
                })
            })
            .then(r => r.json())
            .then(data => {
                const alertDiv = document.getElementById('bulkPreviewAlert');
                if (data.error) {
                    alertDiv.style.background = 'var(--absent-bg)';
                    alertDiv.style.color = 'var(--absent)';
                    alertDiv.textContent = data.error;
                    alertDiv.style.display = 'block';
                    document.getElementById('btnSubmitBulk').disabled = true;
                } else {
                    alertDiv.style.background = 'var(--present-bg)';
                    alertDiv.style.color = 'var(--present)';
                    alertDiv.innerHTML = `
                        <strong>Preview analysis:</strong><br>
                        Records to change: ${data.records_that_will_change}<br>
                        Locked Payroll Conflicts: ${data.locked_payroll_conflicts}<br>
                        Approved leaves skipped: ${data.existing_leave_records}<br>
                        ${data.conflict_message ? '<em>' + data.conflict_message + '</em>' : ''}
                    `;
                    alertDiv.style.display = 'block';
                    document.getElementById('btnSubmitBulk').disabled = data.locked_payroll_conflicts > 0;
                }
            })
            .catch(err => {
                console.error('Error previewing bulk override:', err);
                alert('Error checking conflicts.');
            });
        });

        // Bulk override submit
        document.getElementById('bulkOverrideForm').addEventListener('submit', e => {
            e.preventDefault();
            const checked = document.querySelectorAll('.row-check:checked');
            const empIds = Array.from(checked).map(cb => cb.dataset.emp);
            const dateMode = document.getElementById('bulkDateMode').value;
            const date = document.getElementById('bulkSingleDate').value;
            const start = document.getElementById('bulkStartDate').value;
            const end = document.getElementById('bulkEndDate').value;
            const status = document.getElementById('bulkStatus').value;
            const classification = document.getElementById('bulkClass').value;
            const reason = document.getElementById('bulkReason').value;

            fetch('/admin/attendance-ledger/bulk-override', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    employee_ids: empIds,
                    date_mode: dateMode,
                    date: date,
                    start_date: start,
                    end_date: end,
                    status: status,
                    classification: classification,
                    override_reason: reason,
                    working_days_only: document.getElementById('bulkWorkingDays').checked,
                    skip_leaves: document.getElementById('bulkSkipLeaves').checked,
                    skip_overrides: document.getElementById('bulkSkipOverrides').checked,
                    conflict_handling: 'replace'
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                } else {
                    alert(data.message);
                    closeDrawer();
                    window.location.reload();
                }
            })
            .catch(err => {
                console.error('Error applying bulk override:', err);
                alert('Error applying overrides.');
            });
        });

        /* 3D tilt controls */
        function attachTilt(el, max = 5) {
            if (el._tiltAttached) return;
            el._tiltAttached = true;
            el.addEventListener('mousemove', e => {
                const r = el.getBoundingClientRect();
                const px = (e.clientX - r.left) / r.width;
                const py = (e.clientY - r.top) / r.height;
                const rx = (0.5 - py) * max * 2;
                const ry = (px - 0.5) * max * 2;
                el.style.transform = `perspective(800px) rotateX(${rx.toFixed(2)}deg) rotateY(${ry.toFixed(2)}deg) translateY(-2px)`;
            });
            el.style.transition = 'transform 0.1s ease-out';
            el.addEventListener('mouseleave', () => { el.style.transform = ''; });
        }
        function attachTiltAll(selector) {
            document.querySelectorAll(selector).forEach(el => attachTilt(el));
        }

        // Init page load
        refreshAnalytics();
        renderMatrix();
        updateBulkBar();
    </script>
@endsection
