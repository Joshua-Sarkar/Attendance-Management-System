# 04. Payroll Rules

This document details the business rules, salary deduction structures, and calculations linking daily attendance states and leaves to employee compensation.

---

## 1. Salary Deduction & Time Rules

### Intended Business Rule
Payroll calculations are based on daily presence and leave metrics. Compensation deductions are calculated using the following rules:
- **Full Paid Day**: Any day marked as `present` with `full_day` classification, or covered by an approved `paid_leave` (planned), or a `complimentary` (birthday) leave, or `wfh` (Work From Home) status, or a default `weekly_off` (Sunday) is a full paid day (no deductions).
- **Absent Deduction**: A day resolved as `absent` (excluding Sundays) results in a **1.0-day salary deduction** (LWP - Leave Without Pay).
- **Unpaid Leave / Unplanned Leave Deduction**: A day covered by an approved **Unpaid Leave** or **Unplanned Leave** request results in a **1.0-day salary deduction**.
- **Half-Day Deduction**: A day classified as a `half_day` (regardless of whether it is due to a late arrival or checking out with insufficient hours) results in a **0.5-day salary deduction**.
- **Overtime and Holidays**: Standard national holidays are paid. Work on public holidays or Sundays is subject to double pay or compensatory off policies.

### Current Implementation
- **Status Mapping**: The system generates daily statuses (`present`, `late`, `on_leave`, `wfh`, `absent`, `weekly_off`) and classifications (`full_day`, `half_day`) in the database.
- **Service Layer**: [AttendanceService@getEmployeeStats](file:///c:/Users/Lenovo/AMS-V1/app/Services/AttendanceService.php#L364-L447) aggregates counts of present, late, absent, on_leave, and wfh days.
- **Deduction Processing**: There is **no database or controller implementation** for payroll or salary calculation in the current release. The payroll system is deferred to the next development phase (Phase 6).
- **Payroll Profile Setup**: The employee profile (`employee_profiles.payroll_type` column) caches the payroll type (e.g. `'salaried'`, `'contract'`) parsed by the Zimyo Excel Import Engine or manually entered in the Dossier panel, ready for payroll run processing.

### Current Leave & Override Classifications
- **Paid Leaves**: Mapped under `planned` (Planned Leave) and `complimentary` (Birthday Leave) types. Approved planned requests deduct regular balance; complimentary requests consume birthday credit instead, avoiding regular deductions.
- **Unpaid Leaves**: Mapped under `unpaid` (Unpaid Leave) and `unplanned` (Unplanned Leave) types. They bypass balance checks and deductions, logging a `0.00` ledger entry but triggering salary deductions during payroll preparation.
- **Attendance Overrides**: Daily override status parameters include `'paid_leave'` and `'unpaid_leave'` tags, which synchronously adjust leave balances and write matching ledger adjustment entries.

### Future Improvements
- Create a `PayrollService` to run at the end of each calendar month. The service will query the `attendances` table and `leave_requests` to compute the total payable days:
  $$\text{Payable Days} = \text{Total Calendar Days} - \text{Absent Days} - \text{Unpaid Leaves} - (0.5 \times \text{Half Days})$$
- Generate monthly payslip rows and export them as PDF or sync them via CSV to the accounting system.

---

## 2. Related Modules & Cross References
- **[02_ATTENDANCE_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/02_ATTENDANCE_RULES.md)**: Feeds the status tags and half-day classifications to payroll.
- **[03_LEAVE_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/03_LEAVE_RULES.md)**: Governs leave allocations.
- **[06_METRICS_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/06_METRICS_RULES.md)**: Uses the same attendance service logs to evaluate employee performance ratios.
