# AMS-V1 — Feature & Subsystem Traceability Map

This document decomposes the Attendance Management System Version 1 (AMS-V1) into independently traceable architectural domains, mapping features and business requirements directly to the codebase components.

---

## 1. Authentication, Onboarding & RBAC

### Business Purpose
To secure personnel records and operations by regulating application access based on verified identities, enforcing secure password rules, forcing immediate password updates for newly provisioned or reset accounts, and partitioning functional permissions across three clear roles (`admin`, `manager`, `employee`).

### Architecture Lineage
* **Original Business Problem:** The organization lacked centralized access controls. Personnel details and emergency/financial records were shared over unsecured channels or Excel sheets, exposing private data to unauthorized staff.
* **Phase Introduced:** Phase B (initial Laravel Breeze setup and layout configuration).
* **Major Evolutions Across Releases:**
  * *Phase C.1 / Database Foundation:* Created basic role columns in database table `users`.
  * *Phase D:* Added self-referencing hierarchy keys (`manager_id`, `admin_id`) to the `users` table to restrict manager queries. Added the `must_change_password` boolean attribute.
  * *Phase E:* Built the `CheckPasswordChange` middleware interceptor, redirecting unprovisioned users to standard change-password forms.
* **Current Implementation:** A hybrid of Laravel Breeze's default cookie-session authentication combined with the custom `CheckPasswordChange` middleware. Administrators can reset an employee's password to default, which automatically re-arms the forced update flag.
* **Related Migrations:**
  * [0001_01_01_000000_create_users_table.php](file:///c:/Users/Lenovo/AMS-V1/database/migrations/0001_01_01_000000_create_users_table.php) (initial users schema)
  * [2026_06_10_104616_add_provisioning_columns_to_users_table.php](file:///c:/Users/Lenovo/AMS-V1/database/migrations/2026_06_10_104616_add_provisioning_columns_to_users_table.php) (adds `must_change_password` and onboarding flags)
  * [2026_06_11_134500_add_admin_id_to_users_table.php](file:///c:/Users/Lenovo/AMS-V1/database/migrations/2026_06_11_134500_add_admin_id_to_users_table.php) (adds administrative audit tracking key)
* **Related Tests:**
  * [AuthenticationTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/Auth/AuthenticationTest.php) (standard login validation)
  * [PasswordStrategySecurityTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/PasswordStrategySecurityTest.php) (asserts password updates and forced redirection flows)
* **Related Decisions:**
  * [ADR 1: Onboarding Credential Enforcement](file:///c:/Users/Lenovo/AMS-V1/docs/DECISION_LOG.md#adr-1-onboarding-credential-enforcement-forced-password-reset) (forces password updates upon first-time login)

### Codebase Mappings
* **Controllers:**
  * [AuthenticatedSessionController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/Auth/AuthenticatedSessionController.php) (login / logout)
  * [PasswordController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/Auth/PasswordController.php) (interactive force-reset onboarding controls)
  * [PasswordResetLinkController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/Auth/PasswordResetLinkController.php) (forgot-password workflow)
  * [NewPasswordController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/Auth/NewPasswordController.php) (password recovery link validation)
* **Models:**
  * [User.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/User.php) (contains `role` and `must_change_password` attributes)
* **Services / Middleware:**
  * [CheckPasswordChange.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Middleware/CheckPasswordChange.php) (forces password update before letting users reach dashboards)
  * [EnsureUserIsAdmin.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Middleware/EnsureUserIsAdmin.php) (protects administrative routes)
* **Routes:**
  * `login` / `logout` (declared in [auth.php](file:///c:/Users/Lenovo/AMS-V1/routes/auth.php))
  * `password.change` (view for onboarding updates)
  * `password.change.update` (saves new onboarding password and disables force-reset flag)
  * `admin.employees.reset-password` (forces reset back to default)
* **Views:**
  * `resources/views/auth/login.blade.php`
  * `resources/views/auth/change-password.blade.php`
* **Migrations:**
  * `0001_01_01_000000_create_users_table.php` (sets up basic users table)
  * `2026_06_10_104616_add_provisioning_columns_to_users_table.php` (adds `must_change_password` and onboarding flags)
  * `2026_06_11_134500_add_admin_id_to_users_table.php` (adds administrative audit tracking key)
* **Feature Tests:**
  * [AuthenticationTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/Auth/AuthenticationTest.php) (tests user logins)
  * [PasswordStrategySecurityTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/PasswordStrategySecurityTest.php) (asserts password updates and forced redirection flows)
* **Release Introduced:** `v1.0-phase-d`
* **Current Operational Status:** Fully operational. Self-registration is disabled (commented out in `routes/auth.php`) to keep directory control strictly in administrative hands.

---

## 2. Department & Workforce Management

### Business Purpose
To partition the workforce into distinct business units (departments), manage primary employee user accounts, configure reporting chains (managers and administrators), and prevent security violations by isolating records based on active roles.

### Architecture Lineage
* **Original Business Problem:** The organization lacked structured department boundaries. General staff lists were fully visible to all registered accounts, and managers could view and manage personnel records outside their teams.
* **Phase Introduced:** Phase C.1 (department and employee CRUD directory).
* **Major Evolutions Across Releases:**
  * *Phase C.1:* Developed the basic CRUD routes, controllers, and views for managing department models and staff records.
  * *Phase D:* Integrated self-referencing reporting chains using `manager_id` on the `users` table. Scoped employee listing queries in `EmployeeController.php` so that Managers can only view direct reports.
* **Current Implementation:** Managers and Administrators can perform CRUD operations on employees. Managers are restricted to employees reporting directly to them and cannot create or promote Admin/Manager roles. Employee IDs are auto-generated sequentially under the format `EMP` + 5 digits (e.g. `EMP00001`).
* **Related Migrations:**
  * [2026_06_09_141744_modify_users_table_for_ams.php](file:///c:/Users/Lenovo/AMS-V1/database/migrations/2026_06_09_141744_modify_users_table_for_ams.php) (adds role, status, employee_id keys)
  * [2026_06_09_142514_create_departments_table.php](file:///c:/Users/Lenovo/AMS-V1/database/migrations/2026_06_09_142514_create_departments_table.php) (creates departments schema)
* **Related Tests:**
  * [HierarchySplitTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/HierarchySplitTest.php) (checks department boundaries, reporting scopes, and manager constraints)
* **Related Decisions:**
  * [ADR 2: Sequential Alphanumeric Employee ID Auto-Generation](file:///c:/Users/Lenovo/AMS-V1/docs/DECISION_LOG.md#adr-2-sequential-alphanumeric-employee-id-auto-generation) (auto-generates readable sequential employee IDs)

### Codebase Mappings
* **Controllers:**
  * [DepartmentController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/DepartmentController.php) (CRUD for departments)
  * [EmployeeController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/EmployeeController.php) (CRUD for workforce users, validation limits)
* **Models:**
  * [Department.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/Department.php)
  * [User.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/User.php) (contains primary employee fields: status, role, manager_id, department_id)
* **Services:**
  * [EmployeeService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/EmployeeService.php) (delegates database updates)
* **Routes:**
  * `departments` resources (standard Laravel endpoints)
  * `employees` resource routes
* **Views:**
  * `resources/views/departments/index.blade.php`, `create.blade.php`, `edit.blade.php`
  * `resources/views/employees/index.blade.php`, `create.blade.php`, `edit.blade.php`, `show.blade.php`
* **Migrations:**
  * `2026_06_09_142514_create_departments_table.php`
  * `2026_06_09_141744_modify_users_table_for_ams.php` (role, status, employee_id keys)
* **Feature Tests:**
  * [HierarchySplitTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/HierarchySplitTest.php) (checks department boundaries, active workforce metrics, role-based visibility, and blocks managers from creating admin profiles)
* **Release Introduced:** `v1.0-phase-c.1` (CRUD baseline) and `v1.0-phase-d` (hierarchy controls)
* **Current Operational Status:** Fully operational. Sequential employee ID auto-generation is managed inside `EmployeeController@generateEmployeeId`. Circular manager assignments are blocked during validation.

---

## 3. Employee Profiles

### Business Purpose
To store extended employee metadata (addresses, education, bank accounts, emergency contacts, previous jobs) securely and automatically encrypt sensitive government identifiers and banking fields at rest using AES-256 encryption.

### Architecture Lineage
* **Original Business Problem:** Sensitive identity identifiers (Aadhaar numbers, PAN, bank account numbers, IFSC codes) were stored in plain text, violating data protection protocols. Additionally, importing experience fields (e.g. "5 Years 2 Months") failed because database tables defined experience duration as numeric float fields.
* **Phase Introduced:** Phase 4 (extended profiles and encrypted fields).
* **Major Evolutions Across Releases:**
  * *Phase 4:* Created `employee_profiles` table to isolate extended metadata in a 1:1 mapped relationship to `users`. Integrated multi-tab views and enabled model-level encryption casts.
  * *Phase 4.3:* Altered experience columns from decimal fields to nullable strings to prevent uploader crashes when parsing text duration formats (e.g. "3 Years 6 Months").
* **Current Implementation:** Profile data is managed across organized tabs. Eloquent model encryption casts automatically handle AES-256 encryption at rest.
* **Related Migrations:**
  * [2026_06_18_093324_create_employee_profiles_table.php](file:///c:/Users/Lenovo/AMS-V1/database/migrations/2026_06_18_093324_create_employee_profiles_table.php) (sets up profiles schema)
  * [2026_06_19_084725_change_experience_columns_to_strings_in_employee_profiles.php](file:///c:/Users/Lenovo/AMS-V1/database/migrations/2026_06_19_084725_change_experience_columns_to_strings_in_employee_profiles.php) (experience columns refactor)
* **Related Tests:**
  * [EmployeeProfileTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/EmployeeProfileTest.php) (verifies 1:1 relation lifecycle and database encryption casts)
  * [EmployeeProfileAccessTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/EmployeeProfileAccessTest.php) (verifies role-based read scopes for profile records)
* **Related Decisions:**
  * [ADR 3: Model-Level Encryption Casts for Sensitive Identification & Financial Columns](file:///c:/Users/Lenovo/AMS-V1/docs/DECISION_LOG.md#adr-3-model-level-encryption-casts-for-sensitive-identification--financial-columns)
  * [ADR 4: Alphanumeric String Schemas for Experience Fields](file:///c:/Users/Lenovo/AMS-V1/docs/DECISION_LOG.md#adr-4-alphanumeric-string-schemas-for-experience-fields)

### Codebase Mappings
* **Controllers:**
  * [EmployeeController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/EmployeeController.php) (saves profile tabs data)
  * [ProfileController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/ProfileController.php) (updates basic user settings)
* **Models:**
  * [EmployeeProfile.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/EmployeeProfile.php) (implements database encryption casts)
* **Routes:**
  * `employees.edit` / `employees.update`
  * `profile.edit` / `profile.update`
* **Views:**
  * `resources/views/employees/create.blade.php`, `edit.blade.php`, `show.blade.php` (organized tabs layout)
* **Migrations:**
  * `2026_06_18_093324_create_employee_profiles_table.php` (creates profiles table)
  * `2026_06_19_084725_change_experience_columns_to_strings_in_employee_profiles.php` (experience fields type change)
* **Feature Tests:**
  * [EmployeeProfileTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/EmployeeProfileTest.php) (verifies 1:1 bidirectional relations, cascade deletion on user delete, and database encryption casts validation)
  * [EmployeeProfileAccessTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/EmployeeProfileAccessTest.php) (verifies access boundary rules for profiles)
* **Release Introduced:** `v1.1-phase-4` (Profiles) and `v1.1-phase-4.3` (Experience fixes)
* **Current Operational Status:** Fully operational. Aadhaar, PAN, Bank Account Number, and IFSC Code are encrypted automatically. Alphanumeric experience values import successfully.

---

## 4. Attendance Tracking & Auditing

### Business Purpose
To log employee daily check-in and check-out timestamps, evaluate check-in delays against configurable start times and grace periods, bypass Sunday weekend runs, integrate active leave request status overrides, and present HR with a queryable punctuality audit center.

### Architecture Lineage
* **Original Business Problem:** Inaccurate manual spreadsheets, lack of grace and late duration calculations, absent status wrongly assigned to employees on approved leave, and no global visibility of punctuality metrics.
* **Phase Introduced:** Phase C (attendance check-in/out foundation).
* **Major Evolutions Across Releases:**
  * *Phase C:* Built the core check-in/out engine, using a 09:00 AM shift start with a 15-minute grace window.
  * *Phase E:* Implemented Rule B: approved leaves automatically override absent flags, displaying as `on_leave` or `wfh` unless overwritten by a physical clock-in.
  * *Phase 4.4:* Built the HR Punctuality Audit Center, introducing filtering, late exceptions list, and delay metrics calculation.
  * *Phase 4.5:* Transited the shift start time from 09:00 to 09:30 using a configurable threshold date (`new_rules_start_date`) to protect historical logs from rules corruption.
* **Current Implementation:** `Attendance` records dynamically compute delay durations using carbon tools in [Attendance.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/Attendance.php) depending on the configuration settings. HR admins query global records in the audit console.
* **Related Migrations:**
  * [2026_06_10_000000_create_attendances_table.php](file:///c:/Users/Lenovo/AMS-V1/database/migrations/2026_06_10_000000_create_attendances_table.php) (establishes unique constraint on date and user)
* **Related Tests:**
  * [AttendanceVerificationTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/AttendanceVerificationTest.php) (check-in/out controller tests)
  * [AttendanceMetricsTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/AttendanceMetricsTest.php) (shift grace period calculations)
  * [AttendanceAuditTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/AttendanceAuditTest.php) (HR audit filters)
  * [WorkingDaysTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/WorkingDaysTest.php) (Sunday weekend exclusions)
* **Related Decisions:**
  * [ADR 5: Configurable Historical Shift Rules Transition Strategy](file:///c:/Users/Lenovo/AMS-V1/docs/DECISION_LOG.md#adr-5-configurable-historical-shift-rules-transition-strategy-0900-vs-0930-shift-start)

### Codebase Mappings
* **Controllers:**
  * [AttendanceController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/AttendanceController.php) (clock buttons, dashboard queries)
  * [ManagerAttendanceController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/ManagerAttendanceController.php) (roster views)
  * [AttendanceAuditController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/AttendanceAuditController.php) (HR console log filters)
* **Models:**
  * [Attendance.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/Attendance.php) (late minutes attribute)
* **Services:**
  * [AttendanceService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/AttendanceService.php) (computes statuses, average delays, and exceptions lists)
* **Routes:**
  * `attendance.check-in` / `attendance.check-out`
  * `admin.attendance.logs` (Punctuality Audit panel)
* **Views:**
  * `resources/views/attendance/employee-dashboard.blade.php`
  * `resources/views/admin/attendance-logs.blade.php`
* **Migrations:**
  * `2026_06_10_000000_create_attendances_table.php` (sets up unique index on `[user_id, date]` to prevent concurrent double check-ins)
* **Feature Tests:**
  * [AttendanceVerificationTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/AttendanceVerificationTest.php) (clock inputs)
  * [AttendanceMetricsTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/AttendanceMetricsTest.php) (delay thresholds math)
  * [AttendanceAuditTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/AttendanceAuditTest.php) (global filter queries)
  * [WorkingDaysTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/WorkingDaysTest.php) (Sunday exclusions)
* **Release Introduced:** `v1.0-phase-c` (Foundation) and `v1.1-phase-4.4` (Audit Center)
* **Current Operational Status:** Fully operational. Saturday is processed as a standard workday; Sunday is marked as `weekend` and skipped from absent aggregations.

---

## 5. Leave Request Management

### Business Purpose
To allow employees to submit leave applications under Planned, Unplanned, and Birthday Leave categories, route requests to designated supervisors, dynamically resolve daily attendance status outcomes (`on_leave` vs `absent`) based on approval status for future payroll consumption, and manage complimentary leave credits (Birthday Leave) with automated locking and expiration thresholds.

### Architecture Lineage
* **Original Business Problem:** Leaves were managed via emails and verbal agreements. Staff had to classify their own leaves (Casual, Sick, LOP), which led to incorrect ledger entries and verification bottlenecks.
* **Phase Introduced:** Phase E (leave request schema and approvals).
* **Major Evolutions Across Releases:**
  * *Phase E:* Established the core request and status logs schemas, status transitions, and basic approvals routing.
  * *Phase 4.6:* Simplified workflows by making `leave_type` nullable in the database. General employees submit requests with dates/reasons only; managers classify them as Paid or Unpaid during approval.
  * *Phase 4.7.2:* Completely removed Paid/Unpaid selection. Submissions must select from Planned, Unplanned, or Birthday Leave (complimentary). Balance deduction and salary protection are determined strictly by approval status. Dynamic attendance resolution checks for approved requests to set status to `on_leave`. Exposing a generic `leave_credits` engine for birthday credits sync, leap year offsets, DOB updates locking, and auto-expirations.
  * *Phase 4.7.3:* Refactored visual contrast and readability. Cleaned up pink/purple accents, aligned status pills to the desaturated tag component standard, and fixed dark text on dark green modal confirm button contrasts.
* **Current Implementation:** Employees apply via simple forms selecting Planned, Unplanned, or Birthday Leave. Approved planned/unplanned requests deduct balance and protect salary. Birthday leaves consume a synced `leave_credits` token. Cancellation, rejection, and overrides restore balances and credits, and recalculate attendance to `absent` (if no check-in exists).
* **Related Migrations:**
  * [2026_06_11_153000_create_leave_requests_table.php](file:///c:/Users/Lenovo/AMS-V1/database/migrations/2026_06_11_153000_create_leave_requests_table.php) (primary table)
  * [2026_06_11_153500_create_leave_request_logs_table.php](file:///c:/Users/Lenovo/AMS-V1/database/migrations/2026_06_11_153500_create_leave_request_logs_table.php) (status audit trail)
  * [2026_06_24_154000_create_leave_credits_table.php](file:///c:/Users/Lenovo/AMS-V1/database/migrations/2026_06_24_154000_create_leave_credits_table.php) (reusable leave credits engine)
  * [2026_06_24_154400_add_leave_credit_id_to_leave_requests.php](file:///c:/Users/Lenovo/AMS-V1/database/migrations/2026_06_24_154400_add_leave_credit_id_to_leave_requests.php) (associates requests with credits)
* **Related Tests:**
  * [LeaveManagementTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/LeaveManagementTest.php) (validates request scopes, overrides, overlaps, and cancellations)
  * [LeaveAuthorizationModelTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/LeaveAuthorizationModelTest.php) (asserts planned/unplanned, birthday leave credits sync/expire, leap years, auto-approvals, and overrides)
* **Related Decisions:**
  * [ADR 6: Simplified Nullable Leave Requests](file:///c:/Users/Lenovo/AMS-V1/docs/DECISION_LOG.md#adr-6-simplified-nullable-leave-requests-leave-type-nullability)
  * [ADR 14: Reusable Leave Credit & Approval-Driven Attendance Resolution Architecture](file:///c:/Users/Lenovo/AMS-V1/docs/DECISION_LOG.md#adr-14-reusable-leave-credit--approval-driven-attendance-resolution-architecture)

### Codebase Mappings
* **Controllers:**
  * [LeaveRequestController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/LeaveRequestController.php) (submits requests, checks balance limits, applies approvals, rejects, cancels, and admin overrides)
* **Models:**
  * [LeaveRequest.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/LeaveRequest.php) (defines dates, total days, and relationship to credits)
  * [LeaveCredit.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/LeaveCredit.php) (leave credit records)
  * [LeaveRequestLog.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/LeaveRequestLog.php) (stores request actions audit trail)
* **Routes:**
  * `leaves.index` (list logs)
  * `leaves.create` / `leaves.store` (blank apply form)
  * `leaves.approve` / `leaves.reject` / `leaves.cancel` / `leaves.override`
* **Views:**
  * `resources/views/leaves/index.blade.php`, `create.blade.php`, `show.blade.php` (renders custom stats and labels)
* **Migrations:**
  * `2026_06_11_153000_create_leave_requests_table.php`
  * `2026_06_24_154000_create_leave_credits_table.php` (creates credits schema)
  * `2026_06_24_154400_add_leave_credit_id_to_leave_requests.php` (links requests and credits)
* **Feature Tests:**
  * [LeaveManagementTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/LeaveManagementTest.php) (asserts request submissions, manager approval boundaries, overlapping date checks, self-cancellation routes, and admin self-approvals)
  * [LeaveAuthorizationModelTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/LeaveAuthorizationModelTest.php) (tests planned/unplanned, birthday leave credits sync/expire, leap years, auto-approvals, and overrides)
* **Release Introduced:** `v1.0-phase-e` (Foundation), `v1.2-phase-4.6` (Simplified Nullable Leave), `v1.2-phase-4.7.2` (Leave Credits & Approval-driven Attendance), and `v1.2-phase-4.7.3` (Readability & Usability Pass)
* **Current Operational Status:** Fully operational. Overlaps are validated, birthday credits are dynamically synced on birthday - 1 day, and attendance resolves correctly.

---

## 6. Leave Accrual & Balance Ledger

### Business Purpose
To manage employee leave balance accounts under a transaction audit model, utilize database concurrency locks to prevent race conditions during rapid reviews, and credit monthly balances using automated console tasks.

### Architecture Lineage
* **Original Business Problem:** User leave balances were updated directly without audit logs. Double-clicking approval buttons rapidly caused database race conditions, resulting in duplicate deductions and negative balances.
* **Phase Introduced:** Phase 4.5 (leave balance ledger and concurrency locks).
* **Major Evolutions Across Releases:**
  * *Phase 4.5:* Replaced direct `users.leave_balance` modifications with a double-entry database transaction ledger. Created monthly accrual and backfill console commands. Configured database-level pessimistic locking (`lockForUpdate`).
* **Current Implementation:** User balance adjustments require writing a matching log entry to the `leave_ledger_entries` table inside database transactions, with query rows locked to serialize concurrent requests.
* **Related Migrations:**
  * [2026_06_23_000000_add_leave_balance_and_ledger_tables.php](file:///c:/Users/Lenovo/AMS-V1/database/migrations/2026_06_23_000000_add_leave_balance_and_ledger_tables.php) (adds balance column and creates entries table)
* **Related Tests:**
  * [LeaveBalanceTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/LeaveBalanceTest.php) (asserts balance adjustments, command accruals, cancellations, overrides, and concurrency bounds)
* **Related Decisions:**
  * [ADR 7: Double-Entry Ledger Ledger for Leave Balances](file:///c:/Users/Lenovo/AMS-V1/docs/DECISION_LOG.md#adr-7-double-entry-ledger-ledger-for-leave-balances)
  * [ADR 8: Pessimistic Row Locking for Leave Balance Modifications](file:///c:/Users/Lenovo/AMS-V1/docs/DECISION_LOG.md#adr-8-pessimistic-row-locking-lockforupdate-for-leave-balance-modifications)

### Codebase Mappings
* **Controllers:**
  * [LeaveRequestController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/LeaveRequestController.php) (applies pessimistic locks and transaction wrappers during approvals/cancellations)
* **Models:**
  * [LeaveLedgerEntry.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/LeaveLedgerEntry.php) (ledger table log model)
  * [User.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/User.php) (tracks `leave_balance` column)
* **Services:**
  * [LeaveBalanceService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/LeaveBalanceService.php) (initializes employee on creation)
* **Console Commands:**
  * [InitializeBalancesCommand.php](file:///c:/Users/Lenovo/AMS-V1/app/Console/Commands/InitializeBalancesCommand.php) (`leaves:initialize-balances`)
  * [AccrueLeavesCommand.php](file:///c:/Users/Lenovo/AMS-V1/app/Console/Commands/AccrueLeavesCommand.php) (`leaves:accrue`)
* **Migrations:**
  * `2026_06_23_000000_add_leave_balance_and_ledger_tables.php` (adds `leave_balance` column to `users` and creates `leave_ledger_entries` table)
* **Feature Tests:**
  * [LeaveBalanceTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/LeaveBalanceTest.php) (checks balance initialization, monthly credits accrual, paid leave deductions, refunds, and admin override balance corrections)
* **Release Introduced:** `v1.1-phase-4.5`
* **Current Operational Status:** Fully operational. Accrual commands check for entries within the current month to prevent duplicate credits (idempotent validation).

---

## 7. Zimyo Excel Import Engine

### Business Purpose
To execute bulk migrations of workforce directories from external platform exports (specifically Zimyo spreadsheets), auto-create missing departments, map supervisor hierarchies without order-of-insertion limitations, configure opening leave balances, and record processing logs.

### Architecture Lineage
* **Original Business Problem:** Migrating personnel lists from external providers manually was slow and prone to errors. Single-pass scripts failed to link manager relationships when a subordinate's row preceded their supervisor's row in the Excel sheet.
* **Phase Introduced:** Phase 4.1 (two-pass Zimyo Excel migration engine).
* **Major Evolutions Across Releases:**
  * *Phase 4.1:* Developed `EmployeeImportService.php` utilizing a two-pass parsing pattern. Pass 1 creates users and profiles. Pass 2 resolves and links the manager relationships.
  * *Phase 4:* Added uploader execution tracking and JSON warnings parsing in the `import_logs` table.
* **Current Implementation:** Administrators post spreadsheets via the import panel. The engine parses data, enforces ID formatting (e.g. `EMP00024`), logs warn logs, and initializes balances.
* **Related Migrations:**
  * [2026_06_18_193234_create_import_logs_table.php](file:///c:/Users/Lenovo/AMS-V1/database/migrations/2026_06_18_193234_create_import_logs_table.php) (logs table)
* **Related Tests:**
  * [ImportEmployeesTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/ImportEmployeesTest.php) (validates parsing, manager resolution loops, and error logging)
* **Related Decisions:**
  * [ADR 9: Two-Pass Manager Resolution on Bulk Imports](file:///c:/Users/Lenovo/AMS-V1/docs/DECISION_LOG.md#adr-9-two-pass-manager-resolution-on-bulk-imports)

### Codebase Mappings
* **Controllers:**
  * [ImportController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/ImportController.php) (manages file upload posts and displays status summaries)
* **Models:**
  * [ImportLog.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/ImportLog.php) (logs processing stats and warnings)
* **Services:**
  * [EmployeeImportService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/EmployeeImportService.php) (two-pass Excel uploader logic)
* **Routes:**
  * `admin.import.show` / `admin.import.handle` (admin restricted)
* **Views:**
  * `resources/views/admin/import-employees.blade.php`
* **Migrations:**
  * `2026_06_18_193234_create_import_logs_table.php` (import logs)
* **Feature Tests:**
  * [ImportEmployeesTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/ImportEmployeesTest.php) (asserts uploader role access, formats validation, manager mappings, and error logging)
* **Release Introduced:** `v1.1-phase-4.1`
* **Current Operational Status:** Fully operational. Standardizes employee IDs using prefix formatting (e.g. converts raw values like `"10"` or `"EMP10"` to sequential `"EMP00010"`).

---

## 8. Profile Correction Requests

### Business Purpose
To allow employees to submit correction requests for incorrect profile details, provide HR Admins with an interactive review queue to resolve discrepancies, and notify Admins of pending items using active count badges.

### Architecture Lineage
* **Original Business Problem:** Employees could not correct errors in their profiles directly without posing data security risks. HR had to modify records manually based on chat messages, leaving no audit trail.
* **Phase Introduced:** Phase 4.2 (employee profile correction form and admin review queue).
* **Major Evolutions Across Releases:**
  * *Phase 4.2:* Created the request queues schema, employee request form inputs, and the HR resolution console dashboard.
  * *Phase 4.6:* Integrated a real-time red notification badge counter on the sidebar panel menu layout to alert HR admins to pending items.
* **Current Implementation:** Employees submit a text request pointing to a specific profile attribute. HR Admins view the queue, apply corrections to the employee profile, and mark requests as resolved.
* **Related Migrations:**
  * [2026_06_19_090000_create_profile_correction_requests_table.php](file:///c:/Users/Lenovo/AMS-V1/database/migrations/2026_06_19_090000_create_profile_correction_requests_table.php) (requests schema)
* **Related Tests:**
  * [ProfileCorrectionRequestTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/ProfileCorrectionRequestTest.php) (asserts uploader role access, duplicate blocks, queue lists, and resolution actions)
* **Related Decisions:**
  * [ADR 10: Profile Correction Requests instead of Direct Profile Editing](file:///c:/Users/Lenovo/AMS-V1/docs/DECISION_LOG.md#adr-10-profile-correction-requests-instead-of-direct-profile-editing)

### Codebase Mappings
* **Controllers:**
  * [ProfileCorrectionRequestController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/ProfileCorrectionRequestController.php) (handles employee store actions and admin resolve actions)
* **Models:**
  * [ProfileCorrectionRequest.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/ProfileCorrectionRequest.php) (pending / resolved status mapping)
* **Routes:**
  * `employee.corrections.store` (employee submit)
  * `admin.corrections.index` / `admin.corrections.resolve` (admin queues)
* **Views:**
  * `resources/views/admin/correction-requests/index.blade.php` (HR list panel)
  * [sidebar.blade.php](file:///c:/Users/Lenovo/AMS-V1/resources/views/components/sidebar.blade.php) (implements the badge alert indicator)
* **Migrations:**
  * `2026_06_19_090000_create_profile_correction_requests_table.php`
* **Feature Tests:**
  * [ProfileCorrectionRequestTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/ProfileCorrectionRequestTest.php) (asserts employee store limits, duplicate submission blocks, admin index visibility, and resolution tracking)
* **Release Introduced:** `v1.1-phase-4.2`
* **Current Operational Status:** Fully operational. Employees are blocked from submitting a new request if a pending request already exists for them (duplicate submission prevention).

---

## 9. Deployment & Infrastructure Operations

### Business Purpose
To ensure secure, predictable, and fully recoverable application releases to Hostinger Linux Shared environments, maintain local developer workspace parity via SQLite configurations, run timezone tests, and document the git branch strategy.

### Architecture Lineage
* **Original Business Problem:** Missing compiled CSS/JS assets in production due to build exclusions. DB operations were executed without snapshot safety, posing database corruption and data loss risks. Inconsistent branching taxonomies caused topic branches to drift.
* **Phase Introduced:** Initial setup (repository foundation, Vite assets compiler, environment baseline).
* **Major Evolutions Across Releases:**
  * *Initial Setup:* Created master configuration folders and timezone definitions.
  * *Phase 4.5:* Standardized Hostinger cPanel deployment playbooks and daily automated MySQL snapshot scripts.
  * *Phase 4.7:* Verified local tags and mapped branching strategies to clean up git logs.
* **Current Implementation:** Deployments are run via SSH commands on Hostinger cPanel. Local environments compile assets using Vite and run unit tests against SQLite in-memory databases. Production uses a MySQL 8.0 transaction database engine. Timezones are locked to `Asia/Kolkata` (IST).
* **Related Migrations:**
  * All 14 system database migrations (governed by CLI `migrate --force` during deployment)
* **Related Tests:**
  * [TimezoneTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/TimezoneTest.php) (validates configuration timezone equals `Asia/Kolkata`)
* **Related Decisions:**
  * [ADR 11: Database Parity Strategy (SQLite for Testing vs MySQL in Production)](file:///c:/Users/Lenovo/AMS-V1/docs/DECISION_LOG.md#adr-11-database-parity-strategy-sqlite-for-testing-vs-mysql-in-production)
  * [ADR 12: Standard Git Branch Strategy (Taxonomy & Mappings)](file:///c:/Users/Lenovo/AMS-V1/docs/DECISION_LOG.md#adr-12-standard-git-branch-strategy-taxonomy--mappings)

### Codebase Mappings
* **Configurations:**
  * `vite.config.js` / `tailwind.config.js` (asset compilation)
  * `config/app.php` (timezone locked to `Asia/Kolkata`)
* **Routes:**
  * Health check route `/up` (Laravel default)
* **Migrations:**
  * All 16 database migrations (executed via standard CLI: `php artisan migrate --force`)
* **Feature Tests:**
  * [TimezoneTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/TimezoneTest.php) (confirms active timezone returns `Asia/Kolkata` to prevent clock ticket timezone mismatches)
* **Release Introduced:** `v1.2-docs-foundation` (ops baseline)
* **Current Operational Status:** Fully operational. Daily MySQL snapshots are configured in cPanel. Rollback playbooks are detailed in `DEPLOYMENT.md`.

---

## Git Branch Strategy & Taxonomy

AMS-V1 uses a structured branch taxonomy to keep development history traceable:
1. **`main` (Active / Production):** The source of truth for all deployed features. Deployed directly to Hostinger production. All release and docs tags point here.
2. **`develop` (Legacy Development):** Used during early phase C/D/E integrations. Now kept as a historical record.
3. **`master` (Legacy Production):** Legacy production branch replaced by `main`.
4. **`phase-d-leave-management` (Topic / Phase Branch):** Historical branch used to write early leave request models.
5. **`ui-layout` / `ui-redesign` (Topic / Feature Branches):** Specialized branches used to test dashboard stylesheets and glassmorphic designs.
6. **`hotfix/[module]-[short-desc]` (Operational / Hotfix):** Created directly from `main` to patch production bugs. Merged back to `main` with annotated tag updates.

