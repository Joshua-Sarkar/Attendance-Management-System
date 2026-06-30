# 08. Module Map

This document is the authoritative file registry of AMS-V1. It maps all active subsystems and planned modules to their respective database tables, code files, views, routes, tests, and dependencies.

---

## 1. Authentication & Security

- **Purpose**: Secures user sessions, handles password hashing, forces onboarding resets, and locks access limits.
- **Responsibilities**:
  - Authenticate logins.
  - Intercept active sessions of un-onboarded users and redirect them to change password.
  - Reset standard passwords to default defaults.
- **Database Tables**: `users` (specifically `password`, `must_change_password`, `role`).
- **Models**: [User.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/User.php)
- **Services**: None.
- **Controllers**:
  - [AuthenticatedSessionController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/Auth/AuthenticatedSessionController.php) (Login/Logout)
  - [PasswordController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/Auth/PasswordController.php) (Password updates)
  - [PasswordResetLinkController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/Auth/PasswordResetLinkController.php) (Recovery email generation)
  - [NewPasswordController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/Auth/NewPasswordController.php) (Verify recovery tokens)
  - [ConfirmablePasswordController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/Auth/ConfirmablePasswordController.php) (Re-authentication)
- **Middleware**: [CheckPasswordChange.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Middleware/CheckPasswordChange.php) (Forced onboarding check)
- **Blade Views**:
  - `resources/views/auth/login.blade.php`
  - `resources/views/auth/change-password.blade.php`
  - `resources/views/auth/forgot-password.blade.php`
  - `resources/views/auth/reset-password.blade.php`
  - `resources/views/components/auth-layout.blade.php`
- **Routes**: Declared in [routes/auth.php](file:///c:/Users/Lenovo/AMS-V1/routes/auth.php).
- **Tests**:
  - [PasswordStrategySecurityTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/PasswordStrategySecurityTest.php) (Checks admin reset, default password validation, and force-onboard flow).
  - `tests/Feature/Auth/AuthenticationTest.php`
- **Related Modules**: Employee Management, Dashboards.
- **External Dependencies**: Laravel Breeze (scaffolding baseline).
- **Files to Modify**:
  - [routes/auth.php](file:///c:/Users/Lenovo/AMS-V1/routes/auth.php)
  - [app/Http/Middleware/CheckPasswordChange.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Middleware/CheckPasswordChange.php)
  - [app/Http/Controllers/Auth/PasswordController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/Auth/PasswordController.php)
  - `resources/views/auth/*`
- **Typical Change Workflow**:
  1. Add new route/view under `routes/auth.php` and `resources/views/auth/`.
  2. Implement backend validation in `PasswordController`.
  3. Register logic tests inside `PasswordStrategySecurityTest.php`.

---

## 2. Employee Management (Workforce Directory)

- **Purpose**: CRUD system for core employee directory variables and details tabs.
- **Responsibilities**:
  - Maintain primary identifiers (Employee ID, Name, Email, Join Date).
  - Manage extended details mapping (Father's name, marital status, emergency contacts).
  - Reset individual passwords to system defaults.
- **Database Tables**: `users`, `employee_profiles` (1:1 mapping).
- **Models**:
  - [User.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/User.php)
  - [EmployeeProfile.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/EmployeeProfile.php) (Handles sensitive PII encryption casts)
- **Services**: [EmployeeService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/EmployeeService.php) (Saves user-profile pairs in transactions)
- **Controllers**:
  - [EmployeeController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/EmployeeController.php) (Admin directory CRUD)
  - [ProfileController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/ProfileController.php) (Self password/email updates)
- **Middleware**: [EnsureUserIsAdmin.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Middleware/EnsureUserIsAdmin.php) (Restricts editing endpoints to Admin)
- **Blade Views**:
  - `resources/views/employees/index.blade.php` (Roster directories list)
  - `resources/views/employees/create.blade.php`
  - `resources/views/employees/edit.blade.php`
  - `resources/views/employees/show.blade.php` (Extended Profile Dossier tabs view)
- **Routes**: `Route::resource('employees', EmployeeController::class)` inside [routes/web.php](file:///c:/Users/Lenovo/AMS-V1/routes/web.php#L56-L58).
- **Tests**:
  - [EmployeeProfileTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/EmployeeProfileTest.php) (Verifies relations, AES-256 field encryption, and cascading deletes).
  - [EmployeeProfileAccessTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/EmployeeProfileAccessTest.php) (Verifies access boundaries).
- **Related Modules**: Department Management, Authentication.
- **External Dependencies**: None.
- **Files to Modify**:
  - [app/Models/User.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/User.php)
  - [app/Models/EmployeeProfile.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/EmployeeProfile.php)
  - [app/Services/EmployeeService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/EmployeeService.php)
  - [app/Http/Controllers/EmployeeController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/EmployeeController.php)
  - `resources/views/employees/*`
- **Typical Change Workflow**:
  1. Add database columns to `users` or `employee_profiles` via migrations.
  2. Map parameters inside fillable arrays and add model validations in `EmployeeController` or `EmployeeService`.
  3. Modify tabs display layout in `employees/show.blade.php`.
  4. Write test scenarios inside `EmployeeProfileTest.php`.

---

## 3. Department Management

- **Purpose**: Groups workforce, sets code keys, and defines shift start/end times.
- **Responsibilities**:
  - Create and edit departments.
  - Define customizable department shift start times and grace margins.
- **Database Tables**: `departments`
- **Models**: [Department.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/Department.php)
- **Services**: None.
- **Controllers**: [DepartmentController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/DepartmentController.php)
- **Middleware**: [EnsureUserIsAdmin.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Middleware/EnsureUserIsAdmin.php) (Restricts write actions)
- **Blade Views**:
  - `resources/views/departments/index.blade.php`
  - `resources/views/departments/create.blade.php`
  - `resources/views/departments/edit.blade.php`
- **Routes**: `Route::resource('departments', DepartmentController::class)` inside [routes/web.php](file:///c:/Users/Lenovo/AMS-V1/routes/web.php#L26-L45).
- **Tests**: Scoped validation inside [HierarchySplitTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/HierarchySplitTest.php).
- **Related Modules**: Employee Management, Attendance Tracking.
- **External Dependencies**: None.
- **Files to Modify**:
  - [app/Models/Department.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/Department.php)
  - [app/Http/Controllers/DepartmentController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/DepartmentController.php)
  - `resources/views/departments/*`
- **Typical Change Workflow**:
  1. Create database schema adjustment.
  2. Implement controller CRUD updates.
  3. Modify blade view structures.

---

## 4. Attendance Tracking

- **Purpose**: Core clock-in/out registers, late delays calculations, and personal log directories.
- **Responsibilities**:
  - Record daily check-in and check-out timestamps.
  - Evaluate late arrival markers and grace periods.
  - Output daily logs tables and streaks summaries.
- **Database Tables**: `attendances`
- **Models**: [Attendance.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/Attendance.php)
- **Services**: [AttendanceService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/AttendanceService.php) (Drives calculations for hours and presence states)
- **Controllers**:
  - [AttendanceController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/AttendanceController.php) (Employee dashboard buttons, self-history)
  - [ManagerAttendanceController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/ManagerAttendanceController.php) (Roster details lists)
- **Middleware**: Standard authentication.
- **Blade Views**:
  - `resources/views/attendance/employee-dashboard.blade.php` (Clocking console)
  - `resources/views/attendance/my-attendance.blade.php` (Self metrics, 30-day ledger logs)
  - `resources/views/attendance/history.blade.php`
- **Routes**: `attendance.check-in`, `attendance.check-out`, `attendance.my-attendance` mapped in [routes/web.php](file:///c:/Users/Lenovo/AMS-V1/routes/web.php#L61-L77).
- **Tests**:
  - [AttendanceVerificationTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/AttendanceVerificationTest.php) (Checks standard clocking, double clock preventions).
  - [AttendanceMetricsTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/AttendanceMetricsTest.php) (Checks presence, grace period calculations).
  - [WorkingDaysTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/WorkingDaysTest.php) (Checks Saturday presence and Sunday weekly off default rules).
- **Related Modules**: Attendance Overrides, Leave Request Management, Metrics & Audits.
- **External Dependencies**: Carbon (date formatting engine).
- **Files to Modify**:
  - [app/Services/AttendanceService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/AttendanceService.php)
  - [app/Http/Controllers/AttendanceController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/AttendanceController.php)
  - [app/Models/Attendance.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/Attendance.php)
  - `resources/views/attendance/*`
- **Typical Change Workflow**:
  1. Modify logic within `AttendanceService`.
  2. Implement changes to metrics or displays in `AttendanceController` and views.
  3. Validate using `AttendanceMetricsTest`.

---

## 5. Attendance Overrides

- **Purpose**: Admin console to override logs, classify half-days, and record override audit notes.
- **Responsibilities**:
  - Process individual or bulk roster updates.
  - Store original calculations (`automatic_status`, `automatic_classification`) for accountability.
  - Require a minimum 5-character reason note.
- **Database Tables**: `attendances` (Specifically override metadata columns).
- **Models**: [Attendance.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/Attendance.php)
- **Services**: None.
- **Controllers**: [AttendanceOverrideController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/AttendanceOverrideController.php)
- **Middleware**: [EnsureUserIsAdmin.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Middleware/EnsureUserIsAdmin.php) (Enforces admin-only overrides)
- **Blade Views**: Embedded in dashboard controls modally.
- **Routes**: `admin.attendance.override.store` mapped in [routes/web.php](file:///c:/Users/Lenovo/AMS-V1/routes/web.php#L95).
- **Tests**: [AttendanceOverrideTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/AttendanceOverrideTest.php) (Verifies bulk overrides, reason validation, and audit tracking).
- **Related Modules**: Attendance Tracking.
- **External Dependencies**: None.
- **Files to Modify**:
  - [app/Http/Controllers/AttendanceOverrideController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/AttendanceOverrideController.php)
  - [app/Models/Attendance.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/Attendance.php)
- **Typical Change Workflow**:
  1. Add validation constraints inside `AttendanceOverrideController`.
  2. Map fields to the `Attendance` model.
  3. Run the override tests block.

---

## 6. Leave Request Management & Ledger

- **Purpose**: Coordinates leave applications, routes supervisor approvals, and handles double-entry balance ledgers.
- **Responsibilities**:
  - Submit, approve, reject, or cancel Planned and Unplanned leaves.
  - Deduct balance days from users via double-entry ledger inputs.
  - Prevent concurrent balance modifications using database locks.
- **Database Tables**: `leave_requests`, `leave_request_logs`, `leave_ledger_entries`, `users` (balance cached columns).
- **Models**:
  - [LeaveRequest.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/LeaveRequest.php)
  - [LeaveRequestLog.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/LeaveRequestLog.php)
  - [LeaveLedgerEntry.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/LeaveLedgerEntry.php)
- **Services**: [LeaveBalanceService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/LeaveBalanceService.php) (Executes initial balances and processes birthday transactions)
- **Console Commands**:
  - [InitializeBalancesCommand.php](file:///c:/Users/Lenovo/AMS-V1/app/Console/Commands/InitializeBalancesCommand.php) (`leaves:initialize-balances`)
  - [AccrueLeavesCommand.php](file:///c:/Users/Lenovo/AMS-V1/app/Console/Commands/AccrueLeavesCommand.php) (`leaves:accrue` - credits monthly leave balances)
- **Controllers**: [LeaveRequestController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/LeaveRequestController.php) (Approvals queue and histories)
- **Middleware**: Standard authentication.
- **Blade Views**:
  - `resources/views/leaves/index.blade.php` (Request lists and queues)
  - `resources/views/leaves/create.blade.php`
  - `resources/views/leaves/show.blade.php`
- **Routes**: Grouped under `/leaves` resource endpoints in [routes/web.php](file:///c:/Users/Lenovo/AMS-V1/routes/web.php#L80-L87).
- **Tests**:
  - [LeaveManagementTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/LeaveManagementTest.php) (Verifies submission, supervisor boundaries, cancellations).
  - [LeaveBalanceTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/LeaveBalanceTest.php) (Verifies accrual commands, idempotency checks, ledger logs).
- **Related Modules**: Attendance Tracking, Birthday Leave.
- **External Dependencies**: None.
- **Files to Modify**:
  - [app/Http/Controllers/LeaveRequestController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/LeaveRequestController.php)
  - [app/Services/LeaveBalanceService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/LeaveBalanceService.php)
  - [app/Models/LeaveRequest.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/LeaveRequest.php)
  - [app/Models/LeaveLedgerEntry.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/LeaveLedgerEntry.php)
  - `resources/views/leaves/*`
- **Typical Change Workflow**:
  1. Add a status transition rule in `LeaveRequest` or `LeaveRequestController`.
  2. Implement corresponding database adjustments in `LeaveLedgerEntry`.
  3. Update validation logic in `LeaveRequestController`.
  4. Write test scenarios inside `LeaveManagementTest.php`.

---

## 7. Birthday Leave Credits

- **Purpose**: Generates and manages complimentary annual birthday leave tokens.
- **Responsibilities**:
  - Credit 1.00 birthday leave day to user profiles.
  - Automatically unlock token 1 day before the birthday and expire it after 1 year.
  - Handle leap-year adjustments (Feb 29 birthday shifts to Feb 27 on non-leap years).
- **Database Tables**: `leave_credits`, `leave_requests` (Mapping FK tokens).
- **Models**:
  - [LeaveCredit.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/LeaveCredit.php)
  - [User.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/User.php) (Hosts sync and availability functions)
- **Services**: [LeaveBalanceService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/LeaveBalanceService.php) (Handles double locks and consumption checks)
- **Controllers**: [LeaveRequestController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/LeaveRequestController.php) (Auto-approves complimentary type submissions)
- **Blade Views**: `resources/views/leaves/create.blade.php` (Validates active credit states)
- **Routes**: Integrated with leave store endpoints.
- **Tests**: [LeaveAuthorizationModelTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/LeaveAuthorizationModelTest.php) (Checks unlock timing, leap year shifts, tenures eligibility, and restores on overrides).
- **Related Modules**: Leave Request Management.
- **Files to Modify**:
  - [app/Models/User.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/User.php) (sync logic)
  - [app/Services/LeaveBalanceService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/LeaveBalanceService.php) (consumption logic)
- **Typical Change Workflow**:
  1. Update tenure rules or expiration constraints inside `User.php@syncBirthdayCredits`.
  2. Write corresponding test checks in `LeaveAuthorizationModelTest.php`.

---

## 8. Workforce Zimyo Import Engine

- **Purpose**: Bulk onboard and parse employees from Zimyo-formatted Excel files.
- **Responsibilities**:
  - Read files and extract department names, mobile numbers, and personal details.
  - Parse corporate managers structures in two distinct transaction sweeps.
  - Log error indexes for missing details or invalid states.
- **Database Tables**: `import_logs`, `users`, `employee_profiles`, `departments`.
- **Models**: [ImportLog.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/ImportLog.php)
- **Services**: [EmployeeImportService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/EmployeeImportService.php) (The Excel parser Engine)
- **Controllers**: [ImportController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/ImportController.php)
- **Middleware**: [EnsureUserIsAdmin.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Middleware/EnsureUserIsAdmin.php) (Restricted upload access)
- **Blade Views**: `resources/views/admin/import-employees.blade.php` (Displays upload form, logs tables, and warnings)
- **Routes**: `admin.import.show`, `admin.import.handle` inside [routes/web.php](file:///c:/Users/Lenovo/AMS-V1/routes/web.php#L90-L93).
- **Tests**: [ImportEmployeesTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/ImportEmployeesTest.php) (Checks passes, role escalations, missing columns, errors outputs).
- **Related Modules**: Employee Management, Department Management, Leave Request Management.
- **External Dependencies**: PhpSpreadsheet (Excel reader framework).
- **Files to Modify**:
  - [app/Services/EmployeeImportService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/EmployeeImportService.php)
  - [app/Http/Controllers/ImportController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/ImportController.php)
  - `resources/views/admin/import-employees.blade.php`
- **Typical Change Workflow**:
  1. Add column mapping candidates inside `EmployeeImportService@import`.
  2. Add row extraction validation checks.
  3. Test parser output using `ImportEmployeesTest.php`.

---

## 9. Profile Correction Requests

- **Purpose**: Portal for staff to request profile field fixes, with an HR approval dashboard.
- **Responsibilities**:
  - Handle correction submissions.
  - Block duplicate pending requests for the same field.
  - Support resolved status markers and notes fields.
- **Database Tables**: `profile_correction_requests`
- **Models**: [ProfileCorrectionRequest.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/ProfileCorrectionRequest.php)
- **Controllers**: [ProfileCorrectionRequestController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/ProfileCorrectionRequestController.php)
- **Blade Views**:
  - `resources/views/admin/correction-requests/index.blade.php` (HR review console queue)
  - [sidebar.blade.php](file:///c:/Users/Lenovo/AMS-V1/resources/views/components/sidebar.blade.php) (Computes red notification count)
- **Routes**: Mapped under corrections group in [routes/web.php](file:///c:/Users/Lenovo/AMS-V1/routes/web.php#L98-L107).
- **Tests**: [ProfileCorrectionRequestTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/ProfileCorrectionRequestTest.php) (Checks validations, locks, and admin resolving permissions).
- **Related Modules**: Employee Management, Dashboards.
- **Files to Modify**:
  - [app/Http/Controllers/ProfileCorrectionRequestController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/ProfileCorrectionRequestController.php)
  - [app/Models/ProfileCorrectionRequest.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/ProfileCorrectionRequest.php)
  - `resources/views/admin/correction-requests/index.blade.php`

---

## 10. Dashboards (Manager & HR Admin Panels)

- **Purpose**: High-level command consoles providing daily stats, logs audits, and navigation spines.
- **Responsibilities**:
  - Output active metrics summaries.
  - Handle date, department, and text filters queries.
  - List recent check-in/out records.
- **Controllers**: [DashboardController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/DashboardController.php)
- **Services**: [AttendanceService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/AttendanceService.php) (Queries data lists and exception logs lists)
- **Blade Views**:
  - `resources/views/dashboard.blade.php` (Main Dashboard view)
  - `resources/views/components/sidebar.blade.php`
  - `resources/views/components/header.blade.php`
- **Routes**: `/dashboard` route.
- **Tests**: Scoped logic checks within [AttendanceAuditTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/AttendanceAuditTest.php).
- **Related Modules**: Attendance Tracking, Profile Correction Requests.
- **Files to Modify**:
  - [app/Http/Controllers/DashboardController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/DashboardController.php)
  - `resources/views/dashboard.blade.php`
  - `resources/views/components/sidebar.blade.php`

---

## 11. Payroll Integration (Future)

- **Purpose**: Generates salary logs and processes late arrival deductions.
- **Responsibilities**:
  - Compute payable days based on daily presence classification.
  - Calculate salary deductions for unpaid leaves and half-days.
- **Database Tables**: Mapped to read `attendances`, `leave_requests`, and `users`. Future table: `payslips`.
- **Planned Models**: `Payslip`
- **Planned Services**: `PayrollService`
- **Related Modules**: Attendance Tracking, Leave Request Management.

---

## 12. Metrics & Audits (Future)

- **Purpose**: Generates historical stats, tracks monthly trends, and monitors tardiness averages.
- **Responsibilities**:
  - Output graphs mapping attendance history over 90 days.
  - Export custom CSV lists.
- **Database Tables**: Reads `attendances` history logs.
- **Planned Services**: `AnalyticsService`
- **Related Modules**: Attendance Tracking, Dashboards.

---

## 13. Notifications (Future)

- **Purpose**: Alert employees and managers of system events.
- **Responsibilities**:
  - Email managers when employees request leave.
  - Send email confirmation on password reset or onboarding.
- **Database Tables**: `notifications` (Standard Laravel schema).
- **Planned Services**: `NotificationService`
- **Related Modules**: Leave Request Management, Authentication.
