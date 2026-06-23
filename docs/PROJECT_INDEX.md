# AMS-V1 — Project Index

A searchable developer directory linking major application features to their respective controllers, models, services, migrations, routes, views, database tables, tests, and dependencies.

---

## 1. Authentication & Security

Manages user sessions, role checks, default credentials reset, and password modification enforcement.

* **Primary Controllers:**
  * [AuthenticatedSessionController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/Auth/AuthenticatedSessionController.php) (login, logout)
  * [PasswordController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/Auth/PasswordController.php) (password change and provisioning updates)
  * [NewPasswordController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/Auth/NewPasswordController.php) (recovery resets)
  * [PasswordResetLinkController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/Auth/PasswordResetLinkController.php)
  * [ConfirmablePasswordController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/Auth/ConfirmablePasswordController.php)
* **Primary Middleware:**
  * [CheckPasswordChange.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Middleware/CheckPasswordChange.php) (forces reset if password is unconfigured)
  * [EnsureUserIsAdmin.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Middleware/EnsureUserIsAdmin.php) (restricts administrative routes)
* **Primary Models:**
  * [User.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/User.php)
* **Database Tables:**
  * `users`
  * `password_reset_tokens`
  * `sessions`
* **Routes:**
  * `login`
  * `logout`
  * `password.request` / `password.email` (reset link request)
  * `password.reset` / `password.store` (token collection and submit)
  * `password.change` (interactive onboarding update view)
  * `password.change.update` (onboarding update submit)
  * `password.update` (standard user settings reset)
* **Views:**
  * `resources/views/auth/login.blade.php`
  * `resources/views/auth/forgot-password.blade.php`
  * `resources/views/auth/reset-password.blade.php`
  * `resources/views/auth/confirm-password.blade.php`
  * `resources/views/auth/change-password.blade.php`
* **Tests:**
  * `tests/Feature/PasswordStrategySecurityTest.php`
  * `tests/Feature/Auth/AuthenticationTest.php`
  * `tests/Feature/Auth/PasswordConfirmationTest.php`
  * `tests/Feature/Auth/PasswordResetTest.php`
* **Dependencies:**
  * Laravel Breeze (core authentication stack)
  * BCRYPT hashing algorithm (12 rounds)
* **Related Migrations:**
  * [0001_01_01_000000_create_users_table.php](file:///c:/Users/Lenovo/AMS-V1/database/migrations/0001_01_01_000000_create_users_table.php)
  * [2026_06_10_104616_add_provisioning_columns_to_users_table.php](file:///c:/Users/Lenovo/AMS-V1/database/migrations/2026_06_10_104616_add_provisioning_columns_to_users_table.php) (adds `must_change_password`)

---

## 2. Department Management

Manages organizational business units and grouping classifications for employees.

* **Primary Controllers:**
  * [DepartmentController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/DepartmentController.php)
* **Primary Models:**
  * [Department.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/Department.php)
* **Database Tables:**
  * `departments`
* **Routes:**
  * `departments.index` (list all departments)
  * `departments.create` / `departments.store`
  * `departments.edit` / `departments.update`
  * `departments.destroy`
* **Views:**
  * `resources/views/departments/index.blade.php`
  * `resources/views/departments/create.blade.php`
  * `resources/views/departments/edit.blade.php`
* **Tests:**
  * `tests/Feature/HierarchySplitTest.php` (verifies scoped visibility filters)
* **Related Migrations:**
  * [2026_06_09_142514_create_departments_table.php](file:///c:/Users/Lenovo/AMS-V1/database/migrations/2026_06_09_142514_create_departments_table.php)

---

## 3. Employee Directory & Profiles

Governs personnel records, manager-employee mappings, emergency contacts, and sensitive government details.

* **Primary Controllers:**
  * [EmployeeController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/EmployeeController.php)
  * [ProfileController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/ProfileController.php)
* **Primary Models:**
  * [User.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/User.php)
  * [EmployeeProfile.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/EmployeeProfile.php)
* **Primary Services:**
  * [EmployeeService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/EmployeeService.php)
* **Database Tables:**
  * `users` (core attributes, role, department, reporting chain)
  * `employee_profiles` (extended personal, education, address, and bank data)
* **Routes:**
  * `employees.index` / `employees.show`
  * `employees.create` / `employees.store`
  * `employees.edit` / `employees.update`
  * `employees.destroy`
  * `profile.edit` / `profile.update` / `profile.destroy` (standard Breeze self-service)
  * `admin.employees.reset-password` (forces reset back to default)
* **Views:**
  * `resources/views/employees/index.blade.php`
  * `resources/views/employees/create.blade.php`
  * `resources/views/employees/edit.blade.php`
  * `resources/views/employees/show.blade.php`
* **Tests:**
  * `tests/Feature/EmployeeProfileTest.php` (tests structure and cast updates)
  * `tests/Feature/EmployeeProfileAccessTest.php` (validates read boundaries)
* **Dependencies:**
  * Laravel Eloquent model encryption (`casts` layer handles bank/ID attributes automatically)
* **Related Migrations:**
  * [2026_06_18_093324_create_employee_profiles_table.php](file:///c:/Users/Lenovo/AMS-V1/database/migrations/2026_06_18_093324_create_employee_profiles_table.php)
  * [2026_06_19_084725_change_experience_columns_to_strings_in_employee_profiles.php](file:///c:/Users/Lenovo/AMS-V1/database/migrations/2026_06_19_084725_change_experience_columns_to_strings_in_employee_profiles.php)

---

## 4. Attendance Tracking & Auditing

Logs employee check-in and check-out logs, evaluates delay metrics, and aggregates statistics.

* **Primary Controllers:**
  * [AttendanceController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/AttendanceController.php) (employee self-service inputs)
  * [ManagerAttendanceController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/ManagerAttendanceController.php) (manager oversight logs)
  * [AttendanceAuditController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/AttendanceAuditController.php) (global punctuality center)
* **Primary Services:**
  * [AttendanceService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/AttendanceService.php) (houses punctuality logic, filters, and stats aggregators)
* **Primary Models:**
  * [Attendance.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/Attendance.php) (includes dynamic late minutes calculate accessor)
* **Database Tables:**
  * `attendances` (constrained via unique user + date indexes)
* **Routes:**
  * `employee.dashboard` (includes status controls)
  * `attendance.my-attendance` (monthly calendars)
  * `attendance.check-in` / `attendance.check-out`
  * `attendance.history` (personal list log)
  * `admin.attendance.employee.show`
  * `admin.attendance.logs` (admin audit panel)
* **Views:**
  * `resources/views/attendance/employee-dashboard.blade.php`
  * `resources/views/attendance/my-attendance.blade.php`
  * `resources/views/attendance/history.blade.php`
  * `resources/views/attendance/show.blade.php`
  * `resources/views/admin/attendance-logs.blade.php`
* **Tests:**
  * `tests/Feature/AttendanceVerificationTest.php`
  * `tests/Feature/AttendanceMetricsTest.php`
  * `tests/Feature/AttendanceAuditTest.php`
  * `tests/Feature/WorkingDaysTest.php` (validates Sunday weekend exclusions)
* **Dependencies:**
  * Carbon (date calculations)
  * Config parameters `attendance.start_time` and `attendance.grace_minutes`
* **Related Migrations:**
  * [2026_06_10_000000_create_attendances_table.php](file:///c:/Users/Lenovo/AMS-V1/database/migrations/2026_06_10_000000_create_attendances_table.php)

---

## 5. Leave Request & Accrual System

Governs employee leave logs, manager validation checks, admin override updates, and double-entry ledger audits.

* **Primary Controllers:**
  * [LeaveRequestController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/LeaveRequestController.php)
* **Primary Services:**
  * [LeaveBalanceService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/LeaveBalanceService.php) (manages ledger deductions, refunds, and adjustments)
* **Primary Models:**
  * [LeaveRequest.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/LeaveRequest.php) (handles request records)
  * [LeaveRequestLog.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/LeaveRequestLog.php) (logs request history)
  * [LeaveLedgerEntry.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/LeaveLedgerEntry.php) (stores ledger records)
* **Database Tables:**
  * `leave_requests`
  * `leave_request_logs`
  * `leave_ledger_entries`
* **Console Commands:**
  * [InitializeBalancesCommand.php](file:///c:/Users/Lenovo/AMS-V1/app/Console/Commands/InitializeBalancesCommand.php) (`leaves:initialize-balances` backfills opening balances)
  * [AccrueLeavesCommand.php](file:///c:/Users/Lenovo/AMS-V1/app/Console/Commands/AccrueLeavesCommand.php) (`leaves:accrue` runs monthly leave additions)
* **Routes:**
  * `leaves.index` (directory of requests)
  * `leaves.create` / `leaves.store` (blank leave applications)
  * `leaves.show`
  * `leaves.cancel` (employee self-service cancellation)
  * `leaves.approve` (approves request as paid/unpaid)
  * `leaves.reject`
  * `leaves.override` (admin override control panel)
* **Views:**
  * `resources/views/leaves/index.blade.php`
  * `resources/views/leaves/create.blade.php`
  * `resources/views/leaves/show.blade.php`
* **Tests:**
  * `tests/Feature/LeaveManagementTest.php`
  * `tests/Feature/LeaveBalanceTest.php`
* **Dependencies:**
  * MySQL Pessimistic Locks (`lockForUpdate()`) and transactions (`DB::transaction()`) to prevent race conditions during balance updates.
* **Related Migrations:**
  * [2026_06_11_153000_create_leave_requests_table.php](file:///c:/Users/Lenovo/AMS-V1/database/migrations/2026_06_11_153000_create_leave_requests_table.php)
  * [2026_06_11_153500_create_leave_request_logs_table.php](file:///c:/Users/Lenovo/AMS-V1/database/migrations/2026_06_11_153500_create_leave_request_logs_table.php)
  * [2026_06_23_000000_add_leave_balance_and_ledger_tables.php](file:///c:/Users/Lenovo/AMS-V1/database/migrations/2026_06_23_000000_add_leave_balance_and_ledger_tables.php)
  * [2026_06_23_184204_make_leave_type_nullable_in_leave_requests_table.php](file:///c:/Users/Lenovo/AMS-V1/database/migrations/2026_06_23_184204_make_leave_type_nullable_in_leave_requests_table.php)

---

## 6. Zimyo Migration Engine

Bulk Excel parsing pipeline to import employees, configure profiles, map manager chains, and initialize balances.

* **Primary Controllers:**
  * [ImportController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/ImportController.php)
* **Primary Services:**
  * [EmployeeImportService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/EmployeeImportService.php)
* **Primary Models:**
  * [ImportLog.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/ImportLog.php)
* **Database Tables:**
  * `import_logs` (stores run summaries and parsed warning logs)
* **Routes:**
  * `admin.import.show` (upload panel view)
  * `admin.import.handle` (file processor submit)
* **Views:**
  * `resources/views/admin/import-employees.blade.php`
* **Tests:**
  * `tests/Feature/ImportEmployeesTest.php`
* **Dependencies:**
  * `PhpOffice\PhpSpreadsheet` library
  * Environment variable `DEFAULT_EMPLOYEE_PASSWORD`
* **Related Migrations:**
  * [2026_06_18_193234_create_import_logs_table.php](file:///c:/Users/Lenovo/AMS-V1/database/migrations/2026_06_18_193234_create_import_logs_table.php)

---

## 7. Profile Correction Requests

Allows employees to submit profile corrections, prompting review notifications for Admins.

* **Primary Controllers:**
  * [ProfileCorrectionRequestController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/ProfileCorrectionRequestController.php)
* **Primary Models:**
  * [ProfileCorrectionRequest.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/ProfileCorrectionRequest.php)
* **Database Tables:**
  * `profile_correction_requests`
* **Routes:**
  * `employee.corrections.store`
  * `admin.corrections.index` (review queue list view)
  * `admin.corrections.resolve` (approval status updates)
* **Views:**
  * `resources/views/admin/correction-requests/index.blade.php`
* **Tests:**
  * `tests/Feature/ProfileCorrectionRequestTest.php`
* **Related Migrations:**
  * [2026_06_19_090000_create_profile_correction_requests_table.php](file:///c:/Users/Lenovo/AMS-V1/database/migrations/2026_06_19_090000_create_profile_correction_requests_table.php)

---

## 8. Dashboard Analytics & Theme Layouts

Aggregates statistics and registers reactive tilt behaviors and transitions on layouts.

* **Primary Controllers:**
  * [DashboardController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/DashboardController.php)
  * [AttendanceController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/AttendanceController.php)
* **Views:**
  * `resources/views/dashboard.blade.php` (Manager/Admin stats panel)
  * `resources/views/attendance/employee-dashboard.blade.php` (Employee calendar page)
  * `resources/views/components/sidebar.blade.php` (unified sticky left panel menu)
  * `resources/views/layouts/app.blade.php` (core master HTML envelope containing tick timelines and tilt functions)
* **Styles & Compilation Configs:**
  * [app.css](file:///c:/Users/Lenovo/AMS-V1/resources/css/app.css) (CSS variables, glass panels, tilt cards, tags)
  * `vite.config.js` (asset compilation)
  * `tailwind.config.js`
* **Dependencies:**
  * Font sets: `Fraunces`, `IBM Plex Sans`, `IBM Plex Mono` (loaded via Google Fonts API)
  * PostCSS and Tailwind compilers
