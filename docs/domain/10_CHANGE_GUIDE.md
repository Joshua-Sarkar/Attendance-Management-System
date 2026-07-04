# 10. Change Guide & Impact Matrix

This document provides developers with a subsystem-by-subsystem guide and modification playbooks to evaluate dependencies and prevent regressions during future development.

---

## 1. Subsystem Change Reference Guides

---

### 1. Authentication & Security

**If you modify this subsystem...**
* **Which files must also be reviewed?**
  - [CheckPasswordChange.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Middleware/CheckPasswordChange.php) (Onboarding interceptor)
  - [PasswordController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/Auth/PasswordController.php) (Change route actions)
  - [AuthenticatedSessionController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/Auth/AuthenticatedSessionController.php) (Login/logout handling)
* **Which tests must be updated?**
  - [PasswordStrategySecurityTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/PasswordStrategySecurityTest.php) (Onboarding reset behaviors)
  - [AuthenticationTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/Auth/AuthenticationTest.php) (Login flows)
* **Which documentation must be synchronized?**
  - [01_SYSTEM_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/01_SYSTEM_RULES.md) (Security controls)
  - [08_MODULE_MAP.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/08_MODULE_MAP.md) (File registry)
* **Which configuration values may be affected?**
  - `config/auth.php` (Session thresholds)
  - `config/employees.php` (Default employee password)

---

### 2. Employee Management (Workforce Directory)

**If you modify this subsystem...**
* **Which files must also be reviewed?**
  - [EmployeeController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/EmployeeController.php) (Admin CRUD logic)
  - [EmployeeService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/EmployeeService.php) (User-profile save transaction)
  - [EmployeeProfile.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/EmployeeProfile.php) (Encryption casts)
  - `resources/views/employees/show.blade.php` (Tabbed profile layout)
* **Which tests must be updated?**
  - [EmployeeProfileTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/EmployeeProfileTest.php) (Relations & encryption)
  - [EmployeeProfileAccessTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/EmployeeProfileAccessTest.php) (RBAC checks)
* **Which documentation must be synchronized?**
  - [05_ORGANIZATION_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/05_ORGANIZATION_RULES.md) (PII & Dossier rules)
  - [TECHNICAL_MAP.md](file:///c:/Users/Lenovo/AMS-V1/docs/TECHNICAL_MAP.md) (Schemas and maps)
* **Which configuration values may be affected?**
  - `config/employees.php` (Default employee password)

---

### 3. Department Management

**If you modify this subsystem...**
* **Which files must also be reviewed?**
  - [DepartmentController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/DepartmentController.php) (CRUD logic)
  - [Department.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/Department.php) (Model rules)
  - [AttendanceTimingResolver.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/AttendanceTimingResolver.php) (Resolving custom shifts)
* **Which tests must be updated?**
  - [HierarchySplitTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/HierarchySplitTest.php) (Department accesses checks)
* **Which documentation must be synchronized?**
  - [05_ORGANIZATION_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/05_ORGANIZATION_RULES.md) (Department structures)
* **Which configuration values may be affected?**
  - [config/attendance.php](file:///c:/Users/Lenovo/AMS-V1/config/attendance.php) (Department overrides config)

---

### 4. Attendance Tracking

**If you modify this subsystem...**
* **Which files must also be reviewed?**
  - [AttendanceService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/AttendanceService.php) (Clock check-in/out calculations)
  - [AttendanceTimingResolver.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/AttendanceTimingResolver.php) (Shift timings resolution)
  - [Attendance.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/Attendance.php) (Minutes calculations & date casts)
  - [AttendanceController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/AttendanceController.php) (Clock buttons handler)
* **Which tests must be updated?**
  - [AttendanceVerificationTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/AttendanceVerificationTest.php) (Log registrations)
  - [AttendanceMetricsTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/AttendanceMetricsTest.php) (Punctuality buffer math)
  - [WorkingDaysTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/WorkingDaysTest.php) (Sunday rules)
  - [TimezoneTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/TimezoneTest.php) (Kolkata offset checks)
* **Which documentation must be synchronized?**
  - [02_ATTENDANCE_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/02_ATTENDANCE_RULES.md) (Clock in/out & shifts logic)
  - [12_SYSTEM_CONFIGURATION.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/12_SYSTEM_CONFIGURATION.md) (Grace configurations)
* **Which configuration values may be affected?**
  - `attendance.start_time` / `attendance.grace_minutes` / `attendance.end_time`
  - `attendance.half_day_threshold_hours` / `attendance.weekly_off_day`
  - `attendance.new_rules_start_date`

---

### 5. Attendance Overrides & Auditing

**If you modify this subsystem...**
* **Which files must also be reviewed?**
  - [AttendanceOverrideController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/AttendanceOverrideController.php) (Preview and save overrides)
  - [AttendanceService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/AttendanceService.php) (Previews generator & bulk transaction loop)
  - [AttendanceAuditController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/AttendanceAuditController.php) (Roster lists and timeline views)
  - `resources/views/admin/attendance-logs.blade.php` (Alpine tab workspace view layout)
* **Which tests must be updated?**
  - [AttendanceOverrideTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/AttendanceOverrideTest.php) (Audit trail tests)
  - [BulkAttendanceOverrideTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/BulkAttendanceOverrideTest.php) (Conflict previews, leave balance refunds, and exclusions checks)
* **Which documentation must be synchronized?**
  - [02_ATTENDANCE_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/02_ATTENDANCE_RULES.md) (Administrative override guidelines)
  - [DECISION_LOG.md](file:///c:/Users/Lenovo/AMS-V1/docs/DECISION_LOG.md) (Conflict handling ADR entries)
* **Which configuration values may be affected?**
  - `attendance.allow_negative_leave_balance` (Deduction policies check)

---

### 6. Leave Request Management

**If you modify this subsystem...**
* **Which files must also be reviewed?**
  - [LeaveRequestController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/LeaveRequestController.php) (Leave approval, cancel, and reclassification override endpoints)
  - [LeaveRequest.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/LeaveRequest.php) (Leave type configurations)
  - [LeaveRequestLog.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/LeaveRequestLog.php) (Audit change logs)
* **Which tests must be updated?**
  - [LeaveManagementTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/LeaveManagementTest.php) (Approvals & cancellations)
  - [LeaveLeaveRulesPhase56Test.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/LeaveLeaveRulesPhase56Test.php) (Type validations checks)
* **Which documentation must be synchronized?**
  - [03_LEAVE_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/03_LEAVE_RULES.md) (Planned/Unplanned rules)
* **Which configuration values may be affected?**
  - `attendance.leave_annual_allocation` / `attendance.leave_monthly_accrual_rate`

---

### 7. Birthday Leave Credits

**If you modify this subsystem...**
* **Which files must also be reviewed?**
  - [User.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/User.php) (sync credit & available token loops)
  - [LeaveCredit.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/LeaveCredit.php) (Status properties)
  - [LeaveBalanceService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/LeaveBalanceService.php) (`submitBirthdayLeave` consumption write)
* **Which tests must be updated?**
  - [LeaveAuthorizationModelTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/LeaveAuthorizationModelTest.php) (Unlock buffers, Feb 29 leap shifts, tenure overrides, restores)
* **Which documentation must be synchronized?**
  - [03_LEAVE_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/03_LEAVE_RULES.md) (Birthday parameters)
* **Which configuration values may be affected?**
  - `attendance.birthday_leave_unlock_days`
  - `attendance.birthday_leave_expiry_years`

---

### 8. Leave Balance Ledger

**If you modify this subsystem...**
* **Which files must also be reviewed?**
  - [LeaveLedgerEntry.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/LeaveLedgerEntry.php) (Transaction rows writes)
  - [AccrueLeavesCommand.php](file:///c:/Users/Lenovo/AMS-V1/app/Console/Commands/AccrueLeavesCommand.php) (Accrual console check)
  - [LeaveBalanceService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/LeaveBalanceService.php) (Opening credits initializations)
* **Which tests must be updated?**
  - [LeaveBalanceTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/LeaveBalanceTest.php) (Idempotent commands, cancels refund ledger writes)
* **Which documentation must be synchronized?**
  - [03_LEAVE_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/03_LEAVE_RULES.md) (Double-entry parameters)
* **Which configuration values may be affected?**
  - `attendance.leave_monthly_accrual_rate`

---

### 9. Workforce Zimyo Import Engine

**If you modify this subsystem...**
* **Which files must also be reviewed?**
  - [EmployeeImportService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/EmployeeImportService.php) (Excel parser, manager match lookup checks)
  - [ImportController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/ImportController.php) (Upload post route)
  - `resources/views/admin/import-employees.blade.php` (Import UI templates)
* **Which tests must be updated?**
  - [ImportEmployeesTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/ImportEmployeesTest.php) (Loops and validation indexes checks)
* **Which documentation must be synchronized?**
  - [05_ORGANIZATION_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/05_ORGANIZATION_RULES.md) (Uploader hierarchy rules)
* **Which configuration values may be affected?**
  - `config/employees.php` (Default employee password)

---

### 10. Profile Correction Requests

**If you modify this subsystem...**
* **Which files must also be reviewed?**
  - [ProfileCorrectionRequestController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/ProfileCorrectionRequestController.php) (Resolution updates)
  - [ProfileCorrectionRequest.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/ProfileCorrectionRequest.php) (Status casts)
  - `resources/views/admin/correction-requests/index.blade.php` (Queue lists)
* **Which tests must be updated?**
  - [ProfileCorrectionRequestTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/ProfileCorrectionRequestTest.php) (Locks and resolution scopes)
* **Which documentation must be synchronized?**
  - [05_ORGANIZATION_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/05_ORGANIZATION_RULES.md) (Edit requests rules)
* **Which configuration values may be affected?**
  - None.

---

### 11. Dashboards

**If you modify this subsystem...**
* **Which files must also be reviewed?**
  - [DashboardController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/DashboardController.php) (Metrics builder)
  - `resources/views/dashboard.blade.php` (Dashboard cards grids)
  - `resources/views/components/sidebar.blade.php` (Sidebar layout)
* **Which tests must be updated?**
  - [AttendanceAuditTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/AttendanceAuditTest.php) (Statistics aggregation validations)
* **Which documentation must be synchronized?**
  - [TECHNICAL_MAP.md](file:///c:/Users/Lenovo/AMS-V1/docs/TECHNICAL_MAP.md) (Roster metrics lists)
* **Which configuration values may be affected?**
  - None.

---

## 2. Typical Playbooks for Common Adjustments

### A. Adjusting Shift Timings or Grace Periods
1. **Config file**: Update defaults in `config/attendance.php` if global thresholds change.
2. **Database migration**: Modify default values in the `departments` table migration (`grace_minutes` or `shift_start_time`).
3. **Audit checks**: Review `AttendanceService@checkIn` and `Attendance@getLateMinutesAttribute`. They must align on timing lookups.
4. **Validation checks**: Verify that existing shift tests inside `AttendanceMetricsTest.php` and `AttendanceOverrideTest.php` are passing.

### B. Modifying Birthday Leave Tenure/Eligibilities
1. **Model Helpers**: Modify the tenure calculation loops in `User.php@syncBirthdayCredits`. Adjust constraints on joining date.
2. **Auto-expire rules**: Change duration inside `syncBirthdayCredits` (e.g. if the credit validity changes from 1 year to 6 months).
3. **Submissions verification**: Update validations inside `LeaveRequestController@store` or `LeaveBalanceService::submitBirthdayLeave`.
4. **Run Verification**: Execute `vendor/bin/pest --filter LeaveAuthorizationModelTest`.

### C. Adding a Field to the Zimyo Excel Import Engine
1. **Target Tables**: Add columns via migrations to `users` or `employee_profiles` if it's PII data.
2. **Parser Columns**: Edit [EmployeeImportService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/EmployeeImportService.php) to locate headers mapping candidates in Pass 1. Add extraction keys to `$profileData`.
3. **UI Preview Tables**: Update the upload summary tables inside `admin/import-employees.blade.php`.
4. **Validation Test**: Write a mock excel row in `ImportEmployeesTest.php` and assert the database column saves the parsed value.
