# 02. Attendance & Override Rules

This document details the business logic and current implementation guidelines for tracking daily employee presence, shift timings, tardiness grace periods, weekend exemptions, and administrative overrides.

---

## 1. Daily Check-in & Check-out Logic

### Intended Business Rule
- **Single Log**: Employees can check in once and check out once per calendar day.
- **Chronological Restraint**: A check-out cannot occur before a check-in.
- **Calculated Duration**: The system calculates the absolute time difference between the first check-in and the final check-out to derive the total working hours.

### Current Implementation
- Executed via `checkIn` and `checkOut` in [AttendanceService](file:///c:/Users/Lenovo/AMS-V1/app/Services/AttendanceService.php) and routed through [AttendanceController](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/AttendanceController.php).
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
- **Authoritative Calculations**: There must be a single timing component resolving timings, grace thresholds, and shift ends across controllers, service classes, model accessors, and dashboard calculations.

### Current Implementation
- All attendance calculations are resolved by **[AttendanceTimingResolver](file:///c:/Users/Lenovo/AMS-V1/app/Services/AttendanceTimingResolver.php)**:
  - Department timings are fetched dynamically.
  - **Healthcare Department** is matched case-insensitively by name/code `healthcare` or code `hlt` (with whitespace trimmed) and is hard-overridden via configuration parameters (`attendance.departments.healthcare`) to always resolve to:
    - **Shift Start**: `10:00:00`
    - **Shift End**: `18:00:00`
    - **Grace Minutes**: `5` minutes (grace threshold is `10:05:00`)
  - All other departments resolve from database columns, falling back to configuration default rules.
- Model accessor `late_minutes` in [Attendance](file:///c:/Users/Lenovo/AMS-V1/app/Models/Attendance.php) queries `AttendanceTimingResolver::resolveTimings()`, removing duplicate timing logic and direct model-to-service dependencies.
- The dashboard streak calculation loops call `AttendanceTimingResolver::resolveTimings()` per day, making employee dashboard widgets 100% consistent with department assignments.

---

## 3. Working Hours & Classification Splits

### Intended Business Rule
- **Full Day**: Employees who arrive on time and work at least 4.0 hours are classified as `full_day`.
- **Half Day (Late Arrival)**: Arriving late (past the grace threshold) automatically classifies the day as `half_day` with reason `late_arrival` regardless of total hours worked.
- **Half Day (Insufficient Hours)**: Arriving on time but checking out with less than 4.0 hours of total working time classifies the day as `half_day` with reason `insufficient_hours`.

### Current Implementation
- Classification is initialized during check-in inside `AttendanceService@checkIn` using the resolved timings.
- Arriving late (past grace threshold) resolves the classification using `attendance.late_arrival_classification` configuration (e.g. mapping to `half_day` or `full_day` dynamically).
- During checkout, `AttendanceService@checkOut` calculates total hours and calls `AttendanceTimingResolver::isInsufficientHours($hours)` (which checks config `attendance.half_day_threshold_hours`).
  - If hours are insufficient and the classification is not already `half_day` (and if config policy dictates), it overwrites `automatic_classification = 'half_day'` and sets reason to `'insufficient_hours'`. If not overridden, it updates `classification` to `half_day`.

---

## 4. Weekend (Weekly Off) Rules

### Intended Business Rule
- **Weekly Off**: Weekends are configured globally (e.g. `'Sunday'`). Employees are not expected to work.
- **Absent Protection**: Employees must not be marked as `absent` on weekly off days. Their default status is `weekly_off`.
- **Saturday is a Working Day**: Saturdays are standard working days unless configured otherwise. If an employee has no check-in or approved leave on a Saturday, they must fall back to `absent` status.

### Current Implementation
- Handled dynamically in [AttendanceService](file:///c:/Users/Lenovo/AMS-V1/app/Services/AttendanceService.php) and view models by calling `AttendanceTimingResolver::isWeeklyOff($date)` (linked to config `'attendance.weekly_off_day'`).
  - If no check-in record exists and `isWeeklyOff($date)` is true, a virtual attendance record is returned with `status = 'weekly_off'` or rendered as `weekend` on the UI.

---

## 5. Leave Priority Rules (Rule B Overrides)

### Intended Business Rule
- **Leave Priority**: Approved leave requests (including Work From Home) override the default `absent` status. If no check-in exists on a given day, the status is evaluated as `on_leave` or `wfh`.
- **Physical Check-in Override**: If an employee physically checks in (clocks in) on a day they have an approved leave request, the physical check-in overrides the leave request. The day's status is recalculated as `present` or `late` based on their check-in time.

### Current Implementation
- Implemented in [AttendanceService@getTodayAttendance](file:///c:/Users/Lenovo/AMS-V1/app/Services/AttendanceService.php):
  - Queries `leave_requests` for an approved request overlapping the target date.
  - If found, returns a virtual attendance model with status `on_leave` or `wfh` and classification `full_day`.
  - Physical check-in writes a real database row in `attendances` which naturally takes precedence in queries over the leave request scan since `Attendance::where(...)` is evaluated first.

---

## 6. Administrative Overrides & Ledger Synchronization

### Intended Business Rule
- **Auditable Adjustments**: Administrators can manually override any employee's daily attendance record (status and/or classification).
- **Mandatory Rationale**: All override actions must be accompanied by a justification note (minimum 5 characters) for accountability.
- **Traceability**: The system must preserve the original computed status and classification as an audit trail.
- **Ledger Synchronization**: Overriding attendance to `paid_leave` or resetting it back must synchronously deduct or refund leave balances in the double-entry Leave Ledger.
- **Negative Balance Constraint**: Overrides must validate the employee's remaining leaves. If configuration `'attendance.allow_negative_leave_balance'` is set to `false`, overrides that push the employee's balance below `0.00` must be rejected.
- **Concurrency Protections**: All override modifications must execute inside database transactions wrapping pessimistic row-level locks (`lockForUpdate()`) to prevent concurrent updates from corrupting ledger balances.
- **Bulk Override Workspace**: Administrators must be able to perform roster overrides in bulk, selecting a subset of employees (individually, by department, or all) and a date range (single date, range of dates, or multiple selected dates).
- **Conflict Handling**: When overriding records that overlap with existing manual overrides or approved leave requests, the administrator must choose how to handle conflicts:
  - **Skip**: Ignores conflict records, leaving them unchanged.
  - **Replace**: Overwrites the conflict records, re-evaluating leave balance refunds/deductions.
  - **Cancel**: Aborts the entire bulk operation if any conflict is detected.
- **Override Exclusions**: Administrators can choose to filter the bulk overrides using options:
  - **Working Days Only**: Excludes weekends from the date range.
  - **Include Sundays**: Explicitly applies overrides to Sundays even if working days only is checked.
  - **Skip Leaves**: Automatically skips dates covered by approved leave requests.
  - **Skip Overrides**: Automatically skips dates covered by prior manual overrides.

### Current Implementation
- Handled in [AttendanceOverrideController](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/AttendanceOverrideController.php) and processed by `getBulkOverridePreview` and `applyBulkOverride` inside [AttendanceService](file:///c:/Users/Lenovo/AMS-V1/app/Services/AttendanceService.php).
- Selecting **Half Day** in the Override Status dropdown resolves internally to status `present` and classification `half_day`. The separate override classification selection is removed from the form for simplicity.
- The **Preview** method calculates affected record volumes, existing overrides count, leaves count, and returns a detailed status warning/conflict message based on the selected conflict handling parameters.
- Wraps override commits in a transaction (`DB::transaction`) and locks User and Attendance records:
  - Computes `alreadyDeducted` (checks previous `'paid_leave'` override or approved leave request) and `targetDeduction` (checks new override status/classification weight: `1.0` for full day, `0.5` for half day `'paid_leave'`).
  - Net adjustment `adjustment = alreadyDeducted - targetDeduction` is computed:
    - If balance policy prevents negative balances (`config('attendance.allow_negative_leave_balance') === false`) and the transaction would cause user's balance to fall below zero, it throws an exception aborting the database write.
    - Otherwise, updates `users.leave_balance` cache and creates a matching row in `leave_ledger_entries` of type `'adjustment'`.
  - Saves the final status and classification, sets `is_overridden = true`, records the admin ID (`overridden_by`), override reason, and type (`individual` or `bulk`).

---

## 7. Related Modules & Cross References
- **[03_LEAVE_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/03_LEAVE_RULES.md)**: Governs approved leaves and balance ledger entries.
- **[04_PAYROLL_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/04_PAYROLL_RULES.md)**: Relies on classifications (`half_day`) and statuses (`absent`) for payroll computations.
- **[12_SYSTEM_CONFIGURATION.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/12_SYSTEM_CONFIGURATION.md)**: Manages shift, grace, and negative balance configuration values.
