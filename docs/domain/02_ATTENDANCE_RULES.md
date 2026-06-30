# 02. Attendance & Override Rules

This document details the business logic and current implementation guidelines for tracking daily employee presence, shift timings, tardiness grace periods, weekend exemptions, and administrative overrides.

---

## 1. Daily Check-in & Check-out Logic

### Intended Business Rule
- **Single Log**: Employees can check in once and check out once per calendar day.
- **Chronological Restraint**: A check-out cannot occur before a check-in.
- **Calculated Duration**: The system calculates the absolute time difference between the first check-in and the final check-out to derive the total working hours.

### Current Implementation
- Executed via `checkIn` and `checkOut` in [AttendanceService](file:///c:/Users/Lenovo/AMS-V1/app/Services/AttendanceService.php#L16-L108) and routed through [AttendanceController](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/AttendanceController.php).
- The arrival timestamp is written to `attendances.check_in_time`, and checkout to `attendances.check_out_time`.
- In-progress hours (for today) are calculated dynamically on the dashboard by comparing the check-in time against the current time (`now()`).

### Known Inconsistencies
- If an employee checks in and checks out multiple times in a day, only the first check-in and the last check-out are saved. There is no intermediate break tracking (e.g. lunch breaks).
- Time zones are assumed to be the default PHP configuration. There is no user-specific timezone handling.

### Future Improvements
- Add support for multiple check-ins/check-outs per day to support break tracking.
- Sync biometric hardware clocks directly with the web database via secure webhooks.

---

## 2. Grace Periods, Shifts & Tardiness Calculations

### Intended Business Rule
- **Department-Driven Shifts**: Each department specifies its own shift start time (e.g. `09:30:00`) and grace period in minutes (e.g., `5` or `15` minutes).
- **Grace Boundary**: If an employee checks in on or before the shift start time plus the grace minutes, they are marked as `present`. If they check in after this threshold, they are marked as `late`.
- **Shift Transition (Historical Threshold)**: For employees without a department, historical records before a transition threshold date use a `09:00` start time with a `15` minute grace period. Records on or after this transition date use a `09:30` start time with a `15` minute grace period.

### Current Implementation
- Handled at check-in time in [AttendanceService@checkIn](file:///c:/Users/Lenovo/AMS-V1/app/Services/AttendanceService.php#L21-L38):
  - Resolves `$startTime = $department?->shift_start_time ?? '09:30:00'`.
  - Resolves `$graceMinutes = $department?->grace_minutes ?? 5`.
  - Compares the current time against the threshold. If greater, writes `status = 'late'`, `classification = 'half_day'`, and `automatic_classification_reason = 'late_arrival'`.
- Model accessor `late_minutes` in [Attendance@getLateMinutesAttribute](file:///c:/Users/Lenovo/AMS-V1/app/Models/Attendance.php#L42-L81):
  - Uses the transition threshold date from `config('attendance.new_rules_start_date')` to determine rules if the employee has no department.
  - If date >= threshold: uses `09:30` and `15` minutes grace.
  - If date < threshold: uses `09:00` and `15` minutes grace.
  - Returns `checkInTime.diffInMinutes(graceEnd)`.

### Known Inconsistencies & Discrepancies
> [!WARNING]
> **Recording vs Accessor Logic Discrepancy**:
> There is a severe logic mismatch between `AttendanceService@checkIn` and the `Attendance` model's `late_minutes` calculation for employees without a department:
> 1. `AttendanceService@checkIn` defaults to `09:30` shift start and **5 minutes grace** (threshold `09:35`) if a department is missing. It writes `status = 'late'` immediately if check-in is past `09:35`.
> 2. `Attendance` model's `getLateMinutesAttribute` defaults to config `attendance.grace_minutes` which is **15 minutes grace** (threshold `09:45`) for post-transition dates.
> 
> **Impact**: If an unassigned employee clocks in at `09:40`:
> - `AttendanceService` marks them `status = 'late'` and `classification = 'half_day'` in the database.
> - But `Attendance@late_minutes` evaluates `09:40` <= `09:45` (15 min grace) and returns `0 late minutes`!
>
> This creates an impossible state where an employee is database-marked as `late` but has `0` late minutes in view models.

### Future Improvements
- Consolidate the calculation rules so both the service layer and model accessor use the exact same calculation function.

---

## 3. Working Hours & Classification Splits

### Intended Business Rule
- **Full Day**: Employees who arrive on time and work at least 4.0 hours are classified as `full_day`.
- **Half Day (Late Arrival)**: Arriving late (past the grace threshold) automatically classifies the day as `half_day` with reason `late_arrival` regardless of total hours worked.
- **Half Day (Insufficient Hours)**: Arriving on time but checking out with less than 4.0 hours of total working time classifies the day as `half_day` with reason `insufficient_hours`.

### Current Implementation
- Classification is initialized during check-in inside `AttendanceService@checkIn`.
- During checkout, `AttendanceService@checkOut` reviews the hours:
  - If `automatic_classification_reason` is not already `'late_arrival'`, it checks if `hours < 4.0`.
  - If hours are insufficient, it overwrites `automatic_classification = 'half_day'` and sets reason to `'insufficient_hours'`. If not overridden, it updates `classification` to `half_day`.

### Known Inconsistencies
- If an employee checks in but fails to check out, their hours remain uncalculated and they are not automatically flagged as `insufficient_hours` unless the daily shift rolls over and is audited.

---

## 4. Weekend (Weekly Off) Rules

### Intended Business Rule
- **Weekly Off**: Sundays are standard rest days. Employees are not expected to work.
- **Absent Protection**: Employees must not be marked as `absent` on Sundays. Their default status is `weekly_off`.
- **Saturday is a Working Day**: Saturdays are standard working days. If an employee has no check-in or approved leave on a Saturday, they must fall back to `absent` status.

### Current Implementation
- Handled dynamically in [AttendanceService@getTodayAttendance](file:///c:/Users/Lenovo/AMS-V1/app/Services/AttendanceService.php#L136-L145) and stats queries:
  - If no check-in record exists and `date.isSunday()` is true, a virtual attendance record is returned with `status = 'weekly_off'`.
  - Checked in [WorkingDaysTest](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/WorkingDaysTest.php). Saturday falls back to `absent`, and Sunday falls back to `weekly_off`.

---

## 5. Leave Priority Rules (Rule B Overrides)

### Intended Business Rule
- **Leave Priority**: Approved leave requests (including Work From Home) override the default `absent` status. If no check-in exists on a given day, the system evaluates approved leaves and displays the status as `on_leave` or `wfh`.
- **Physical Check-in Override**: If an employee physically checks in (clocks in) on a day they have an approved leave request, the physical check-in overrides the leave request. The day's status is recalculated as `present` or `late` based on their check-in time.

### Current Implementation
- Implemented in [AttendanceService@getTodayAttendance](file:///c:/Users/Lenovo/AMS-V1/app/Services/AttendanceService.php#L118-L135):
  - Queries `leave_requests` for an approved request overlapping the target date.
  - If found, returns a virtual attendance model with status `on_leave` or `wfh` and classification `full_day`.
  - Physical check-in writes a real database row in `attendances` which naturally takes precedence in queries over the leave request scan since `Attendance::where(...)` is evaluated first.

---

## 6. Administrative Overrides

### Intended Business Rule
- **Auditable Adjustments**: Administrators can manually override any employee's daily attendance record (status and/or classification).
- **Mandatory Rationale**: All override actions must be accompanied by a justification note (minimum 5 characters) for accountability.
- **Traceability**: The system must preserve the original computed status and classification as an audit trail.

### Current Implementation
- Processed in [AttendanceOverrideController@store](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/AttendanceOverrideController.php#L15-L96).
- If the attendance row does not exist, it is initialized, and `automatic_status` is populated with the default resolved status (`on_leave`, `wfh`, `weekly_off`, or `absent`).
- The manual updates are written to `status` and `classification`.
- Sets `is_overridden = true`, `overridden_by = auth()->id()`, `overridden_at = now()`, and writes the explanation to `override_reason`.

### Known Inconsistencies
- **Flaky Rejection Test Case**: In `LeaveAuthorizationModelTest.php`, the test `planned_leave_rejection_does_not_deduct_balance_and_attendance_resolves_to_absent` defines the target date as `Carbon::today()->addDays(5)`. If this target date lands on a Sunday (as it does when run on Tuesday, June 30, 2026), the resolved status for the day is `weekly_off` instead of `absent`. This causes the test assertion expecting `absent` to fail.
- **Weekly Off Override Gap**: When an override changes the status of a day to `paid_leave` or `unpaid_leave`, this is saved as a status tag on the attendance record, but it does **not** update or interact with the double-entry leave ledger. This can lead to balance differences between the ledger and attendance logs.

---

## 7. Related Modules & Cross References
- **[03_LEAVE_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/03_LEAVE_RULES.md)**: Governs approved leaves that feed status overrides.
- **[04_PAYROLL_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/04_PAYROLL_RULES.md)**: Relies on classifications (`half_day`) and statuses (`absent`) for payroll computations.
- **[12_SYSTEM_CONFIGURATION.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/12_SYSTEM_CONFIGURATION.md)**: Manages shift and grace period values.
