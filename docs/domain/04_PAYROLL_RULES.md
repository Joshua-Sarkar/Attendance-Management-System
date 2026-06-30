# 04. Payroll Rules

This document details the business rules, salary deduction structures, and calculations linking daily attendance states and leaves to employee compensation.

---

## 1. Salary Deduction & Time Rules

### Intended Business Rule
Payroll calculations are based on daily presence and leave metrics. Compensation deductions are calculated using the following rules:
- **Full Paid Day**: Any day marked as `present` with `full_day` classification, or covered by an approved `paid_leave` (planned or unplanned), or a `complimentary` (birthday) leave, or `wfh` (Work From Home) status, or a default `weekly_off` (Sunday) is a full paid day (no deductions).
- **Absent Deduction**: A day resolved as `absent` (excluding Sundays) results in a **1.0-day salary deduction** (LWP - Leave Without Pay).
- **Unpaid Leave Deduction**: A day covered by a leave request approved specifically as **Unpaid Leave** results in a **1.0-day salary deduction**.
- **Half-Day Deduction**: A day classified as a `half_day` (regardless of whether it is due to a late arrival or checking out with insufficient hours) results in a **0.5-day salary deduction**.
- **Overtime and Holidays**: Standard national holidays are paid. Work on public holidays or Sundays is subject to double pay or compensatory off policies.

### Current Implementation
- **Status Mapping**: The system generates daily statuses (`present`, `late`, `on_leave`, `wfh`, `absent`, `weekly_off`) and classifications (`full_day`, `half_day`) in the database.
- **Service Layer**: [AttendanceService@getEmployeeStats](file:///c:/Users/Lenovo/AMS-V1/app/Services/AttendanceService.php#L364-L447) aggregates counts of present, late, absent, on_leave, and wfh days.
- **Deduction Processing**: There is **no database or controller implementation** for payroll or salary calculation in the current release. The payroll system is deferred to the next development phase (Phase 5).

### Known Inconsistencies & Discrepancies
- **No Paid/Unpaid Flag on Leaves**: The system currently categorizes leaves into `planned`, `unplanned`, and `complimentary` in the controller. The previous Paid vs Unpaid categorization was removed. Currently, standard approved leaves deduct standard balance. If standard balance is insufficient, standard employees cannot submit a Planned leave. It is unclear if there is a separate category for "Unpaid" leave requests, though the override status `'unpaid_leave'` exists in `AttendanceOverrideController@store`.

### Future Improvements
- Create a `PayrollService` to run at the end of each calendar month. The service will query the `attendances` table and `leave_requests` to compute the total payable days:
  $$\text{Payable Days} = \text{Total Calendar Days} - \text{Absent Days} - \text{Unpaid Leaves} - (0.5 \times \text{Half Days})$$
- Generate monthly payslip rows and export them as PDF or sync them via CSV to the accounting system.

---

## 2. Related Modules & Cross References
- **[02_ATTENDANCE_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/02_ATTENDANCE_RULES.md)**: Feeds the status tags and half-day classifications to payroll.
- **[03_LEAVE_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/03_LEAVE_RULES.md)**: Governs leave allocations.
- **[06_METRICS_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/06_METRICS_RULES.md)**: Uses the same attendance service logs to evaluate employee performance ratios.
