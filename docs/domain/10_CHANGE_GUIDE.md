# 10. Change Guide & Impact Matrix

This document provides developers with a step-by-step modification guide and a Change Impact Matrix to ensure future feature development remains predictable and does not break existing logic.

---

## 1. Typical Implementation Workflow

When making modifications or adding features to AMS-V1, follow this checklist sequence:
1. **Analyze Rules**: Read the relevant business rule document inside `docs/domain/` to understand the domain constraints.
2. **Draft Modifications**: Update the rule document if business behaviors are changing, and obtain stakeholder approval first.
3. **Database Migrations**: Add column adjustments or new tables. Never modify active historical columns without a backup plan.
4. **Services and Models**: Implement core business calculations in the service layer (`app/Services/`) and encapsulate relations and casts in models (`app/Models/`).
5. **Controllers and Middleware**: Orchestrate request parameters validation and access controls logic.
6. **Views (Blade)**: Adapt UI elements following the specs in `07_UI_GUIDELINES.md`.
7. **Pest Tests**: Add feature or unit tests checking all success paths, boundaries, and unauthorized routes.
8. **Update Documentation**: Synchronize changes back to this domain knowledge base.

---

## 2. Change Impact Matrix

This matrix maps each major system feature to its configuration, business logic, models, controllers, views, tests, and documentation files. **If a feature changes tomorrow, review the files listed in its row.**

| Feature Name | Business Rules Doc | Database Tables | Models | Services | Controllers | Blade Views | Pest Tests |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Birthday Leave Credits** | `03_LEAVE_RULES.md` | `leave_credits`, `leave_requests` | `User`, `LeaveCredit`, `LeaveRequest` | `LeaveBalanceService` | `LeaveRequestController` | `leaves/create.blade.php` | `LeaveAuthorizationModelTest.php` |
| **Attendance Overrides** | `02_ATTENDANCE_RULES.md` | `attendances` | `Attendance` | None | `AttendanceOverrideController` | Embedded in dashboard modules | `AttendanceOverrideTest.php` |
| **Weekly Off Exclusions** | `02_ATTENDANCE_RULES.md` | None (checks dayOfWeek) | `Attendance` | `AttendanceService` | `AttendanceController` | `attendance/my-attendance.blade.php` | `WorkingDaysTest.php` |
| **Department Shifts** | `02_ATTENDANCE_RULES.md` | `departments` | `Department`, `User` | `AttendanceService` | `DepartmentController` | `departments/*` | `HierarchySplitTest.php` (role check) |
| **Workforce Excel Import** | `05_ORGANIZATION_RULES.md` | `import_logs`, `users`, `employee_profiles` | `ImportLog`, `User`, `EmployeeProfile` | `EmployeeImportService` | `ImportController` | `admin/import-employees.blade.php` | `ImportEmployeesTest.php` |
| **Profile Correction Requests** | `05_ORGANIZATION_RULES.md` | `profile_correction_requests` | `ProfileCorrectionRequest` | None | `ProfileCorrectionRequestController` | `admin/correction-requests/index.blade.php` | `ProfileCorrectionRequestTest.php` |
| **Planned / Unplanned Leaves** | `03_LEAVE_RULES.md` | `leave_requests`, `leave_ledger_entries`, `users` | `LeaveRequest`, `LeaveLedgerEntry`, `User` | `LeaveBalanceService` | `LeaveRequestController` | `leaves/*` | `LeaveManagementTest.php`, `LeaveBalanceTest.php` |
| **Password Strategy & RBAC** | `01_SYSTEM_RULES.md` | `users` | `User` | None | `PasswordController`, `AuthenticatedSessionController` | `auth/*` | `PasswordStrategySecurityTest.php` |

---

## 3. Step-by-Step Playbooks for Common Adjustments

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

---

## 4. Related Modules & Cross References
- **[08_MODULE_MAP.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/08_MODULE_MAP.md)**: Master directory of files.
- **[12_SYSTEM_CONFIGURATION.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/12_SYSTEM_CONFIGURATION.md)**: Coordinates configurable thresholds locations.
- **[README.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/README.md)**: Governs standard documentation update workflows.
