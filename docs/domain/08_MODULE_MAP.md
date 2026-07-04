# 08. Module Map

This document is the authoritative file registry of AMS-V1. It maps all active subsystems and planned future modules to their respective database tables, classes, views, routes, tests, configuration, and documentation files.

---

## 1. Authentication & Security

* **Purpose**: Secures user sessions, handles password hashing, forces onboarding resets, and locks access limits.
* **Database Tables**: `users` (specifically `password`, `must_change_password`, `role`).
* **Models**: [User.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/User.php)
* **Controllers**:
  - [AuthenticatedSessionController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/Auth/AuthenticatedSessionController.php) (Login/Logout)
  - [PasswordController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/Auth/PasswordController.php) (Password updates)
  - [PasswordResetLinkController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/Auth/PasswordResetLinkController.php) (Recovery email generation)
  - [NewPasswordController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/Auth/NewPasswordController.php) (Verify recovery tokens)
  - [ConfirmablePasswordController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/Auth/ConfirmablePasswordController.php) (Re-authentication)
  - [EmailVerificationPromptController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/Auth/EmailVerificationPromptController.php) (Verification prompt)
  - [EmailVerificationNotificationController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/Auth/EmailVerificationNotificationController.php) (Resend verification)
  - [VerifyEmailController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/Auth/VerifyEmailController.php) (Process email verification link)
* **Services**: None.
* **Policies**: None.
* **Middleware**: [CheckPasswordChange.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Middleware/CheckPasswordChange.php) (Forced onboarding check)
* **Requests**: [LoginRequest.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Requests/Auth/LoginRequest.php)
* **Views**:
  - `resources/views/auth/login.blade.php`
  - `resources/views/auth/change-password.blade.php`
  - `resources/views/auth/forgot-password.blade.php`
  - `resources/views/auth/reset-password.blade.php`
  - `resources/views/auth/confirm-password.blade.php`
  - `resources/views/auth/verify-email.blade.php`
  - `resources/views/components/auth-layout.blade.php`
* **Routes**: Declared in [routes/auth.php](file:///c:/Users/Lenovo/AMS-V1/routes/auth.php).
* **Tests**:
  - [PasswordStrategySecurityTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/PasswordStrategySecurityTest.php) (Checks admin reset, default password validation, and force-onboard flow).
  - [SetCommonPasswordCommandTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/SetCommonPasswordCommandTest.php) (Password backfill command test)
  - [AuthenticationTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/Auth/AuthenticationTest.php)
  - [PasswordUpdateTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/Auth/PasswordUpdateTest.php)
  - [PasswordResetTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/Auth/PasswordResetTest.php)
  - [PasswordConfirmationTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/Auth/PasswordConfirmationTest.php)
  - [EmailVerificationTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/Auth/EmailVerificationTest.php)
  - [RegistrationTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/Auth/RegistrationTest.php)
* **Configuration files**: `config/auth.php`
* **Documentation files**:
  - [01_SYSTEM_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/01_SYSTEM_RULES.md)
  - [GIT_STANDARDS.md](file:///c:/Users/Lenovo/AMS-V1/docs/GIT_STANDARDS.md)
* **Any supporting classes**: None.

---

## 2. Employee Management (Workforce Directory)

* **Purpose**: CRUD system for core employee directory variables and details tabs.
* **Database Tables**: `users`, `employee_profiles` (1:1 mapping).
* **Models**:
  - [User.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/User.php)
  - [EmployeeProfile.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/EmployeeProfile.php) (Handles sensitive PII encryption casts)
* **Controllers**:
  - [EmployeeController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/EmployeeController.php) (Admin directory CRUD & Password Resets)
  - [ProfileController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/ProfileController.php) (Self password/email updates)
* **Services**: [EmployeeService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/EmployeeService.php) (Saves user-profile pairs in transactions)
* **Policies**: None.
* **Middleware**: [EnsureUserIsAdmin.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Middleware/EnsureUserIsAdmin.php) (Restricts editing endpoints to Admin)
* **Requests**: [ProfileUpdateRequest.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Requests/ProfileUpdateRequest.php)
* **Views**:
  - `resources/views/employees/index.blade.php` (Roster directories list)
  - `resources/views/employees/create.blade.php`
  - `resources/views/employees/edit.blade.php`
  - `resources/views/employees/show.blade.php` (Extended Profile Dossier tabs view)
* **Routes**: `Route::resource('employees', EmployeeController::class)` inside [routes/web.php](file:///c:/Users/Lenovo/AMS-V1/routes/web.php).
* **Tests**:
  - [EmployeeProfileTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/EmployeeProfileTest.php) (Verifies relations, AES-256 field encryption, and cascading deletes).
  - [EmployeeProfileAccessTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/EmployeeProfileAccessTest.php) (Verifies access boundaries).
  - [ProfileTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/ProfileTest.php) (Standard profile edit tests)
* **Configuration files**: `config/employees.php` (Stores default employee password)
* **Documentation files**:
  - [05_ORGANIZATION_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/05_ORGANIZATION_RULES.md)
  - [TECHNICAL_MAP.md](file:///c:/Users/Lenovo/AMS-V1/docs/TECHNICAL_MAP.md)
* **Any supporting classes**: None.

---

## 3. Department Management

* **Purpose**: Groups workforce, sets code keys, and defines shift start/end times.
* **Database Tables**: `departments`
* **Models**: [Department.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/Department.php)
* **Controllers**: [DepartmentController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/DepartmentController.php)
* **Services**: None.
* **Policies**: None.
* **Middleware**: [EnsureUserIsAdmin.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Middleware/EnsureUserIsAdmin.php) (Restricts write actions to Admin)
* **Requests**: None.
* **Views**:
  - `resources/views/departments/index.blade.php`
  - `resources/views/departments/create.blade.php`
  - `resources/views/departments/edit.blade.php`
  - `resources/views/departments/show.blade.php`
* **Routes**: `Route::resource('departments', DepartmentController::class)` inside [routes/web.php](file:///c:/Users/Lenovo/AMS-V1/routes/web.php).
* **Tests**: [HierarchySplitTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/HierarchySplitTest.php)
* **Configuration files**: None.
* **Documentation files**: [05_ORGANIZATION_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/05_ORGANIZATION_RULES.md)
* **Any supporting classes**: None.

---

## 4. Attendance Tracking

* **Purpose**: Core clock-in/out registers, late delays calculations, and personal log directories.
* **Database Tables**: `attendances`
* **Models**: [Attendance.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/Attendance.php)
* **Controllers**:
  - [AttendanceController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/AttendanceController.php) (Employee dashboard buttons, self-history)
  - [ManagerAttendanceController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/ManagerAttendanceController.php) (Roster details lists for manager direct reports)
* **Services**:
  - [AttendanceService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/AttendanceService.php) (Drives calculations for hours and check-in/out states)
  - [AttendanceTimingResolver.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/AttendanceTimingResolver.php) (Resolves dynamic shift start, end, grace times)
* **Policies**: None.
* **Middleware**: Standard `auth` middleware.
* **Requests**: None.
* **Views**:
  - `resources/views/attendance/employee-dashboard.blade.php` (Clocking console)
  - `resources/views/attendance/my-attendance.blade.php` (Self metrics, 30-day logs)
  - `resources/views/attendance/history.blade.php` (Personal calendar logs history)
* **Routes**: `attendance.check-in`, `attendance.check-out`, `attendance.my-attendance`, `attendance.history`, and `admin.attendance.employee.show` in [routes/web.php](file:///c:/Users/Lenovo/AMS-V1/routes/web.php).
* **Tests**:
  - [AttendanceVerificationTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/AttendanceVerificationTest.php) (Standard clocking, double clock preventions)
  - [AttendanceMetricsTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/AttendanceMetricsTest.php) (Grace periods, late check-in metrics)
  - [WorkingDaysTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/WorkingDaysTest.php) (Sunday weekly off & Saturday working check)
  - [TimezoneTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/TimezoneTest.php) (Enforces Asia/Kolkata timezone verification)
* **Configuration files**: [config/attendance.php](file:///c:/Users/Lenovo/AMS-V1/config/attendance.php)
* **Documentation files**:
  - [02_ATTENDANCE_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/02_ATTENDANCE_RULES.md)
  - [12_SYSTEM_CONFIGURATION.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/12_SYSTEM_CONFIGURATION.md)
* **Any supporting classes**: None.

---

## 5. Attendance Overrides

* **Purpose**: Admin console to override attendance status/classifications individually or in bulk, with conflict handling rules and audit trail logs.
* **Database Tables**: `attendances`, `leave_ledger_entries`, `users`.
* **Models**:
  - [Attendance.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/Attendance.php) (Stores override metadata columns)
  - [LeaveLedgerEntry.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/LeaveLedgerEntry.php) (Writes adjustment lines)
  - [User.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/User.php) (Updates balance cash)
* **Controllers**:
  - [AttendanceOverrideController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/AttendanceOverrideController.php) (Previews overrides, applies individual/bulk rules, resolves conflicts)
  - [AttendanceAuditController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/AttendanceAuditController.php) (Lists overridden timelines)
* **Services**: [AttendanceService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/AttendanceService.php) (Implements `getBulkOverridePreview` and `applyBulkOverride`)
* **Policies**: None.
* **Middleware**: [EnsureUserIsAdmin.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Middleware/EnsureUserIsAdmin.php) (Restricts to admin)
* **Requests**: None.
* **Views**: `resources/views/admin/attendance-logs.blade.php` (Alpine tabbed manager workspace)
* **Routes**:
  - `admin.attendance.logs`
  - `admin.attendance.override.employees`
  - `admin.attendance.override.preview`
  - `admin.attendance.override.store`
* **Tests**:
  - [AttendanceOverrideTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/AttendanceOverrideTest.php) (Individual overrides, audit trails)
  - [BulkAttendanceOverrideTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/BulkAttendanceOverrideTest.php) (Department overrides, conflict options preview/commit)
* **Configuration files**: [config/attendance.php](file:///c:/Users/Lenovo/AMS-V1/config/attendance.php)
* **Documentation files**: [02_ATTENDANCE_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/02_ATTENDANCE_RULES.md)
* **Any supporting classes**: None.

---

## 6. Leave Request Management

* **Purpose**: Coordinates leave applications, routes approvals to managers, and auto-resolves daily attendance mappings.
* **Database Tables**: `leave_requests`, `leave_request_logs`, `users`.
* **Models**:
  - [LeaveRequest.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/LeaveRequest.php) (Tracks status, ranges, and types)
  - [LeaveRequestLog.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/LeaveRequestLog.php) (Audit logs of status transitions)
* **Controllers**: [LeaveRequestController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/LeaveRequestController.php)
* **Services**: None.
* **Policies**: None.
* **Middleware**: Standard auth middleware.
* **Requests**: None (Validation inside controller store)
* **Views**:
  - `resources/views/leaves/index.blade.php` (Queue grid lists)
  - `resources/views/leaves/create.blade.php` (Submissions form)
  - `resources/views/leaves/show.blade.php` (Logs audit view)
* **Routes**: Mapped resource routes under `/leaves` in [routes/web.php](file:///c:/Users/Lenovo/AMS-V1/routes/web.php).
* **Tests**:
  - [LeaveManagementTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/LeaveManagementTest.php) (Requests CRUD, cancellations, approvals, and rejections)
  - [LeaveLeaveRulesPhase56Test.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/LeaveLeaveRulesPhase56Test.php) (Planned/Unplanned/Unpaid validation checks)
* **Configuration files**: [config/attendance.php](file:///c:/Users/Lenovo/AMS-V1/config/attendance.php)
* **Documentation files**: [03_LEAVE_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/03_LEAVE_RULES.md)
* **Any supporting classes**: None.

---

## 7. Birthday Leave Credits

* **Purpose**: Manages complimentary annual birthday leave tokens, leap-year shifts, and expiration rules.
* **Database Tables**: `leave_credits`, `leave_requests`.
* **Models**:
  - [LeaveCredit.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/LeaveCredit.php)
  - [User.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/User.php) (Exposes sync methods)
* **Controllers**: [LeaveRequestController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/LeaveRequestController.php) (Invokes check validations)
* **Services**: [LeaveBalanceService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/LeaveBalanceService.php) (Executes `submitBirthdayLeave` auto-approval transaction)
* **Policies**: None.
* **Middleware**: Standard auth.
* **Requests**: None.
* **Views**: `resources/views/leaves/create.blade.php` (Evaluates `$hasBirthdayCredit` parameter flag)
* **Routes**: Handled on leave submit endpoints.
* **Tests**: [LeaveAuthorizationModelTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/LeaveAuthorizationModelTest.php) (Unlock window, Feb 29 leap offsets, joining limits)
* **Configuration files**: [config/attendance.php](file:///c:/Users/Lenovo/AMS-V1/config/attendance.php) (Unlocks/expiry durations)
* **Documentation files**: [03_LEAVE_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/03_LEAVE_RULES.md)
* **Any supporting classes**: None.

---

## 8. Leave Balance Ledger

* **Purpose**: Coordinates leave balance caches under transaction double-entry ledgers, enforcing pessimistic lock safety boundaries.
* **Database Tables**: `leave_ledger_entries`, `users`.
* **Models**: [LeaveLedgerEntry.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/LeaveLedgerEntry.php)
* **Controllers**: Handled inside `LeaveRequestController` and `AttendanceOverrideController`.
* **Services**: [LeaveBalanceService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/LeaveBalanceService.php) (Initializes opening balance `2.00` credits)
* **Console Commands**:
  - [InitializeBalancesCommand.php](file:///c:/Users/Lenovo/AMS-V1/app/Console/Commands/InitializeBalancesCommand.php) (`leaves:initialize-balances`)
  - [AccrueLeavesCommand.php](file:///c:/Users/Lenovo/AMS-V1/app/Console/Commands/AccrueLeavesCommand.php) (`leaves:accrue` credits monthly balances)
* **Policies**: None.
* **Middleware**: None.
* **Requests**: None.
* **Views**: `resources/views/leaves/index.blade.php` (Displays cached balance)
* **Routes**: Handled on leaves action endpoints.
* **Tests**: [LeaveBalanceTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/LeaveBalanceTest.php) (Accrual checks, idempotency, cancels refund ledger writes)
* **Configuration files**: [config/attendance.php](file:///c:/Users/Lenovo/AMS-V1/config/attendance.php)
* **Documentation files**: [03_LEAVE_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/03_LEAVE_RULES.md)
* **Any supporting classes**: None.

---

## 9. Workforce Zimyo Import Engine

* **Purpose**: Onboards employees bulk-wise from Zimyo spreadsheet directories.
* **Database Tables**: `import_logs`, `users`, `employee_profiles`, `departments`.
* **Models**: [ImportLog.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/ImportLog.php)
* **Controllers**: [ImportController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/ImportController.php)
* **Services**: [EmployeeImportService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/EmployeeImportService.php) (Parser Engine)
* **Policies**: None.
* **Middleware**: [EnsureUserIsAdmin.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Middleware/EnsureUserIsAdmin.php) (Admin-only uploads)
* **Requests**: None.
* **Views**: `resources/views/admin/import-employees.blade.php` (Logs tables, error lists, uploads form)
* **Routes**: `admin.import.show`, `admin.import.handle` in [routes/web.php](file:///c:/Users/Lenovo/AMS-V1/routes/web.php).
* **Tests**: [ImportEmployeesTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/ImportEmployeesTest.php) (Parser loops, missing headers validations, cyclic reporting loops preventions)
* **Configuration files**: [config/employees.php](file:///c:/Users/Lenovo/AMS-V1/config/employees.php)
* **Documentation files**: [05_ORGANIZATION_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/05_ORGANIZATION_RULES.md)
* **Any supporting classes**: None.

---

## 10. Profile Correction Requests

* **Purpose**: Submits profile edits requests, routing them to HR review lists.
* **Database Tables**: `profile_correction_requests`
* **Models**: [ProfileCorrectionRequest.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/ProfileCorrectionRequest.php)
* **Controllers**: [ProfileCorrectionRequestController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/ProfileCorrectionRequestController.php)
* **Services**: None.
* **Policies**: None.
* **Middleware**: Admin middleware for approval endpoints.
* **Requests**: None.
* **Views**:
  - `resources/views/admin/correction-requests/index.blade.php` (HR review queues dashboard)
  - `resources/views/components/sidebar.blade.php` (Sidebar count badge alerts)
* **Routes**: `employee.corrections.store`, `admin.corrections.index`, `admin.corrections.resolve` in [routes/web.php](file:///c:/Users/Lenovo/AMS-V1/routes/web.php).
* **Tests**: [ProfileCorrectionRequestTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/ProfileCorrectionRequestTest.php) (Submission limits, duplicate blocks, Admin resolve)
* **Configuration files**: None.
* **Documentation files**: [05_ORGANIZATION_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/05_ORGANIZATION_RULES.md)
* **Any supporting classes**: None.

---

## 11. Dashboards (Manager & HR Admin Panels)

* **Purpose**: Renders metrics summaries, direct reports rosters, filters check-ins logs, and lists recent clocks histories.
* **Database Tables**: `attendances`, `leave_requests`, `users`, `profile_correction_requests`.
* **Models**: `User`, `Attendance`, `LeaveRequest`.
* **Controllers**: [DashboardController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/DashboardController.php)
* **Services**: [AttendanceService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/AttendanceService.php) (Retrieves metrics and exceptions data)
* **Policies**: None.
* **Middleware**: Standard auth middleware.
* **Requests**: None.
* **Views**:
  - `resources/views/dashboard.blade.php` (General manager/admin console)
  - `resources/views/components/sidebar.blade.php`
  - `resources/views/components/header.blade.php`
* **Routes**: `/dashboard` route.
* **Tests**: Scoped validation inside [AttendanceAuditTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/AttendanceAuditTest.php).
* **Configuration files**: None.
* **Documentation files**: [TECHNICAL_MAP.md](file:///c:/Users/Lenovo/AMS-V1/docs/TECHNICAL_MAP.md)
* **Any supporting classes**: None.

---

## 12. Payroll Integration (Future / Preparation)

* **Purpose**: Stores payroll setup parameters and resolves unpaid absences indicators for external calculations.
* **Database Tables**: Mapped to read `attendances`, `leave_requests`, `users`. Future table: `payslips`.
* **Models**: Planned `Payslip` model.
* **Controllers**: None in current release.
* **Services**: Planned `PayrollService`.
* **Policies**: None.
* **Middleware**: None.
* **Requests**: None.
* **Views**: Displays profile `payroll_type` field in `employees/show.blade.php` tab dossier.
* **Routes**: None.
* **Tests**: None in current release.
* **Configuration files**: [config/attendance.php](file:///c:/Users/Lenovo/AMS-V1/config/attendance.php)
* **Documentation files**: [04_PAYROLL_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/04_PAYROLL_RULES.md)
* **Any supporting classes**: None.

---

## 13. Metrics & Audits (Future)

* **Purpose**: Generates trend charts mapping 90-day attendance metrics, streak calculations, and average delays exports.
* **Database Tables**: Mapped to query `attendances`.
* **Models**: None.
* **Controllers**: None in current release.
* **Services**: Planned `AnalyticsService`.
* **Policies**: None.
* **Middleware**: None.
* **Requests**: None.
* **Views**: None.
* **Routes**: None.
* **Tests**: None.
* **Configuration files**: None.
* **Documentation files**: [06_METRICS_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/06_METRICS_RULES.md)
* **Any supporting classes**: None.

---

## 14. Notifications (Future)

* **Purpose**: Automates email notifications to managers upon employee leave submissions or onboarding password resets.
* **Database Tables**: Mapped to write standard Laravel notifications table schema.
* **Models**: None.
* **Controllers**: None.
* **Services**: Planned `NotificationService`.
* **Policies**: None.
* **Middleware**: None.
* **Requests**: None.
* **Views**: None.
* **Routes**: None.
* **Tests**: None.
* **Configuration files**: `config/mail.php`
* **Documentation files**: [14_NOTIFICATION_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/14_NOTIFICATION_RULES.md)
* **Any supporting classes**: None.

---

## 15. Analytics, Reporting, & Integrations (Future)

* **Purpose**: Future placeholders for workforce analytics trend forecasting, custom CSV/PDF report builders, and external ERP integration layers.
* **Database Tables**: None.
* **Models**: None.
* **Controllers**: None.
* **Services**: None.
* **Policies**: None.
* **Middleware**: None.
* **Requests**: None.
* **Views**: None.
* **Routes**: None.
* **Tests**: None.
* **Configuration files**: None.
* **Documentation files**:
  - [13_REPORTING_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/13_REPORTING_RULES.md)
  - [15_ANALYTICS_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/15_ANALYTICS_RULES.md)
  - [16_INTEGRATION_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/16_INTEGRATION_RULES.md)
  - [17_API_REFERENCE.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/17_API_REFERENCE.md)
* **Any supporting classes**: None.
