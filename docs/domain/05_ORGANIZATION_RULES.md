# 05. Organization & Profile Rules

This document details the rules governing corporate structures, reporting hierarchies, 1:1 employee profiles, and sensitive data privacy boundaries.

---

## 1. Department & Code Mapping Rules

### Intended Business Rule
- **Structural Partitioning**: Every employee must belong to a single department (business unit) identified by a unique code (e.g. `ENG` for Engineering, `HR` for Human Resources).
- **Shift Assignment**: Timings and grace boundaries are defined at the department level to support customized team rosters (e.g., standard shifts vs healthcare shifts).

### Current Implementation
- Handled in [DepartmentController](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/DepartmentController.php) and model [Department](file:///c:/Users/Lenovo/AMS-V1/app/Models/Department.php).
- The `departments` table stores `id`, `name`, `code`, `description`, `shift_start_time`, `shift_end_time`, and `grace_minutes`.
- Deleted departments use a set-null cascade so that employees are not deleted but become unassigned.

---

## 2. Reporting Hierarchy & Constraints

### Intended Business Rule
- **Hierarchical Reporting**: Standard employees must report to a designated Manager. Managers in turn report to an Administrator.
- **Approval Chains**: Leave requests are routed up the hierarchy. A manager can only view records and approve leave requests for employees who directly report to them.
- **Reporting Constraints**:
  - A user cannot report to themselves (no circular self-references).
  - Circular reporting loops (e.g. A reports to B, and B reports to A) must be blocked.
  - An Admin cannot report to a Manager.

### Current Implementation
- Handled in the self-referencing relationship columns `manager_id` and `admin_id` in the `users` table.
- Eloquent mappings:
  - `manager()` BelongsTo relationship mapped to parent User.
  - `directReports()` HasMany relationship.
- Validations are enforced in [HierarchySplitTest](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/HierarchySplitTest.php):
  - Admins can create other Admins.
  - Managers cannot create Admins or other Managers.
  - Circular chains are audited during manual creation or Zimyo bulk sheets imports.

### Known Inconsistencies
- **Import Pass-Through Loops**: The Zimyo Excel import engine creates reporting chains dynamically. While it prevents direct self-reporting, it does **not** recursively traverse the tree to check for multi-level loops (e.g. A reports to B, B reports to C, C reports to A). If such a loop is imported, it will create an infinite loop during database traversal.

---

## 3. Employee Profile 1:1 Mapping & Life Cycles

### Intended Business Rule
- **Dossier Mapping**: Extended employee metadata (personal, emergency, previous experience, banking credentials) must reside in a separate `employee_profiles` table linked 1:1 to the primary credentials `users` record.
- **Lifecycle Parity (Cascade)**: If a user account is deleted from the system, its matching profile record must be purged automatically to avoid orphan records.
- **Experience Formats**: To accommodate non-numeric, descriptive values, columns tracking tenure or experience must accept strings (e.g., `"3 Years, 4 Months"`) rather than decimals.

### Current Implementation
- Mapped in [EmployeeProfile](file:///c:/Users/Lenovo/AMS-V1/app/Models/EmployeeProfile.php) with relation `employeeProfile()` in [User](file:///c:/Users/Lenovo/AMS-V1/app/Models/User.php#L78-L81).
- The table schema uses `ON DELETE CASCADE` foreign key mappings:
  `user_id bigint unsigned FK references users(id) ON DELETE CASCADE`.
- Experience variables `previous_year_experience`, `years_completed`, and `overall_year_experience` are mapped as nullable `varchar(255)` string columns in migrations.

### Known Inconsistencies
- Deleting an employee via the UI is soft or hard? The controller [EmployeeController@destroy](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/EmployeeController.php#L206-L220) executes a hard delete (`$user->delete()`), triggering the database-level cascade. However, this deletes all historical check-in logs (`attendances`) and ledger entries (`leave_ledger_entries`), destroying audit history.

### Future Improvements
- Implement Soft Deletes (`use SoftDeletes`) on the `User` and `EmployeeProfile` models to preserve historical log records for accounting and audit compliance.

---

## 4. Related Modules & Cross References
- **[01_SYSTEM_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/01_SYSTEM_RULES.md)**: Governs data encryption casts for PII fields.
- **[03_LEAVE_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/03_LEAVE_RULES.md)**: Routes leave requests to managers based on these hierarchical relationships.
- **[08_MODULE_MAP.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/08_MODULE_MAP.md)**: Lists the files making up Employee, Department, and Profile modules.
