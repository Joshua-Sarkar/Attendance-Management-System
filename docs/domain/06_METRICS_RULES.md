# 06. Metrics & Performance Rules

This document details the business logic, mathematical formulas, and boundary rules for computing employee performance metrics, on-time streaks, attendance rates, and average late arrivals.

---

## 1. On-Time Streak Calculation

### Intended Business Rule
- **Continuous Performance**: The "on-time streak" measures the consecutive number of working days an employee has arrived on time (`present` status) without being late or absent.
- **Skipping Rest Days**: Weekly Off days (Sundays) and approved leaves (`on_leave` or `wfh` status) must not break the streak. They are ignored (exempted) and skipped during calculation.
- **Streak Break**: A status of `late` or `absent` on any working day immediately resets the streak count to `0` and halts evaluation.
- **Rolling Window**: Calculated over a rolling 90-day evaluation history.
- **Today's Buffer**: If evaluating today and the user has not checked in yet:
  - If the time is before the shift start plus grace minutes, the streak is calculated starting from yesterday (allowing the user time to check in without breaking their streak).
  - If the time is past the shift start plus grace minutes and no check-in exists, the day is considered `absent` and resets the streak.

### Current Implementation
- Programmed in [AttendanceController@employeeDashboard](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/AttendanceController.php#L107-L185):
  - Fetches 90 days of history.
  - Resolves `$threshold` using the configuration start time and grace minutes.
  - If today has no check-in, is not Sunday, and has no approved leave: checks if `now() <= threshold`. If true, begins evaluation from yesterday (`today()->subDay()`).
  - Iterates backwards day-by-day:
    - If status is `weekly_off` or `on_leave`, it calls `continue` (ignores/skips).
    - If status is `present`, increments `$streak`.
    - If status is `late` or `absent`, it execution-breaks out of the loop.

### Known Inconsistencies
- **WFH Exemption vs present**: WFH status is treated as an approved leave in the streak check (`status === 'on_leave' || status === 'wfh'` skips it). This means WFH does **not** increment the on-time streak; it only preserves it. Some business guidelines consider WFH as active presence, which should increment the streak.
- **Department Timings Ignored in Streak**: The controller calculates the streak grace threshold using global config values (`config('attendance.start_time')` and `config('attendance.grace_minutes')`) instead of querying the employee's department shift settings. This means employees in departments with custom shifts (e.g. starting at `08:00`) will have their buffer evaluated against the global default `09:30` threshold.

---

## 2. Monthly Attendance Rate

### Intended Business Rule
- **Formula**: The monthly attendance rate represents the percentage of active days worked relative to the total expected working days:
  $$\text{Attendance Rate} = \left( \frac{\text{Present Days} + \text{WFH Days}}{\text{Present Days} + \text{Absent Days} + \text{Leave Days} + \text{WFH Days}} \right) \times 100$$
- **Exclusion of Weekly Off**: Sundays (weekly off) are completely excluded from both the numerator and the denominator.

### Current Implementation
- Executed dynamically in [AttendanceController@employeeDashboard](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/AttendanceController.php#L27-L102):
  - Loops from the start of the month to today.
  - Counts present, late, wfh, leave, and absent days.
  - Ignores `weekly_off` statuses.
  - Computes the percentage rounded to 1 decimal place.

### Known Inconsistencies
- Approved leave days (`on_leave`) are included in the denominator (total working days) but not the numerator (present). This means taking an approved leave **reduces** the employee's monthly attendance rate.
- **Correction**: Standard HR rules treat approved paid leaves as neutral (excluding them from both the numerator and denominator so leaves do not penalize attendance metrics).

---

## 3. Average Late Arrival Delay

### Intended Business Rule
- **Performance Evaluation**: Tracks the average duration (in minutes) of check-in delays for employees marked as `late` to assess the severity of tardiness.
- **Formula**:
  $$\text{Average Delay} = \frac{\sum \text{Late Minutes}}{\text{Total Late Check-ins}}$$
- **Exclusion**: Present, absent, and leave statuses must be excluded from this calculation.

### Current Implementation
- Calculated in [AttendanceService@getTodayStats](file:///c:/Users/Lenovo/AMS-V1/app/Services/AttendanceService.php#L312-L346):
  - Iterates through the department's active roster.
  - If status is `late`, adds `late_minutes` to `$totalLateMinutes`.
  - Divides by the count of late employees and rounds to 1 decimal place.

---

## 4. Related Modules & Cross References
- **[02_ATTENDANCE_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/02_ATTENDANCE_RULES.md)**: Defines the grace thresholds and late minute calculations.
- **[03_LEAVE_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/03_LEAVE_RULES.md)**: Governs leave statuses that must skip streak calculations.
- **[12_SYSTEM_CONFIGURATION.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/12_SYSTEM_CONFIGURATION.md)**: Stores default start times and rolling check windows.
