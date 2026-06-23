# AMS-V1 — Feature & Subsystem Traceability Map

This document decomposes the Attendance Management System Version 1 (AMS-V1) into independently traceable architectural domains, mapping features and business requirements directly to the codebase components.

---

## 1. Authentication, Onboarding & RBAC

### Business Purpose
To secure personnel records and operations by regulating application access based on verified identities, enforcing secure password rules, forcing immediate password updates for newly provisioned or reset accounts, and partitioning functional permissions across three clear roles (`admin`, `manager`, `employee`).

### Architecture Lineage
* **Original Business Problem:** The organization lacked centralized access logs. Personnel details and emergency/financial records were shared over unsecured channels or Excel sheets, exposing private data to unauthorized staff.
* **Phase Introduced:** Initial Laravel setup (Breeze base) in Phase B / database foundation.
* **Major Evolutions:**
  * *Phase C.1 / Database Foundation:* Created basic role columns in database table `users`.
  * *Phase D:* Added self-referencing hierarchy keys (`manager_id`, `admin_id`) to the `users` table to restrict manager queries. Added the `must_change_password` boolean attribute.
  * *Phase E:* Built the `CheckPasswordChange` middleware interceptor, redirecting unprovisioned users to standard change-password forms.
* **Current Implementation:** A hybrid of Laravel Breeze's default cookie-session authentication combined with the custom `CheckPasswordChange` middleware. Administrators can reset an employee's password to default, which automatically re-arms the forced update flag.

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
* **Phase Introduced:** Phase C.1 (CRUD directory) and Phase D (manager hierarchy scope).
* **Major Evolutions:**
  * *Phase C.1 (Commit `e37dd81`):* Developed the basic CRUD routes, controllers, and views for managing department models and staff records. Created default seed tables.
  * *Phase D (Commit `14a6f80`):* Integrated self-referential reporting chains using `manager_id` on the `users` table. Modified queries in `EmployeeController.php` index action so that Managers can only view employees where `manager_id = auth()->id()`.
* **Current Implementation:** Managers and Administrators can perform CRUD operations on employees, but Managers are restricted to employees reporting directly to them and cannot create Admin or Manager roles. Employee IDs are auto-generated sequentially under the format `EMP` + 5 digits (e.g. `EMP00001`).

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
*(Reconciled in profiles phase)*

---

## 4. Attendance Tracking
*(Reconciled in attendance phase)*

---

## 5. Leave Request Management
*(Reconciled in leaves phase)*

---

## 6. Leave Accrual & Balance Ledger
*(Reconciled in ledger phase)*

---

## 7. Zimyo Excel Import Engine
*(Reconciled in imports phase)*

---

## 8. Profile Correction Requests
*(Reconciled in corrections phase)*

---

## 9. Deployment & Infrastructure Operations
*(Reconciled in operations phase)*
