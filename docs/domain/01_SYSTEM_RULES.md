# 01. System & Security Rules

This document defines the system-wide security, session authentication, onboarding policies, role permissions (RBAC), and encryption standards of the Attendance Management System.

---

## 1. Authentication & Onboarding Rules

### Intended Business Rule
- **Disabled Registration**: Public self-registration is strictly disabled. All user accounts must be provisioned by an administrator or through the Zimyo Excel uploader.
- **Forced Onboarding**: Newly provisioned accounts are assigned a default system password. On their first login, they must be intercepted and forced to change their password to a custom secure password before they can access any dashboards, submit attendance, or request leave.
- **Admin Reset Override**: If an administrator resets an employee's password, the forced onboarding state must be re-armed, requiring the user to change their password upon their next login.

### Current Implementation
- Handled in [CheckPasswordChange](file:///c:/Users/Lenovo/AMS-V1/app/Http/Middleware/CheckPasswordChange.php) middleware which evaluates the `must_change_password` boolean attribute on the authenticated `User` model.
- If `must_change_password = true`, the middleware intercepts the request and redirects the user to the `/password/change` route (named `password.change`), blocking access to other pages.
- Password updates are processed in [PasswordController@storeChange](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/Auth/PasswordController.php#L39-L55), which hashes the new credentials, updates the database, sets `must_change_password = false`, and redirects to the dashboard.
- Admin password resets are executed in [EmployeeController@resetPassword](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/EmployeeController.php#L225-L245), which resets the user's password to the environment config value `employees.default_employee_password` and resets `must_change_password` to `true`.

### Known Inconsistencies
- The `CheckPasswordChange` middleware is registered globally in the `web` group but does not exempt standard guest or login endpoints in a clean structural way, relying on route exclusions.
- If the default password in `.env` is empty, user creation or reset fails silently or throws an exception. This is audited during system startup but there is no runtime fallback.

### Future Improvements
- Implement password complexity requirements (minimum length, special characters, digit enforcement).
- Add password expiration rules (e.g., force a reset every 90 days).

---

## 2. Role-Based Access Control (RBAC) Rules

### Intended Business Rule
The system partitions operational permissions into three roles:
1. **Admin**: Global system visibility. Can manage departments, upload sheets, override daily rosters, resolve profile corrections, approve/override leaves, and reset passwords.
2. **Manager**: Scoped visibility. Can only view attendance logs and review leave requests for employees who are directly assigned to them via the manager reporting hierarchy.
3. **Employee**: Self service. Can clock in/out, view their own history and metrics, edit basic profile attributes, submit correction requests, and apply for leaves.

### Current Implementation
- Admin routing is isolated using the [EnsureUserIsAdmin](file:///c:/Users/Lenovo/AMS-V1/app/Http/Middleware/EnsureUserIsAdmin.php) middleware. Non-admin access results in a `403 Forbidden` abort.
- Manager scoping is checked directly in queries:
  - Inside [DashboardController@index](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/DashboardController.php#L48-L50), `MonitoringUser` is passed to the attendance services to filter employee lists to direct reports only.
  - Inside [LeaveRequestController@index](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/LeaveRequestController.php#L67-L84), the approval queue queries only employees reporting to the logged-in manager (`manager_id = auth()->id()`).
- Employees are redirected to `/employee/dashboard` if they attempt to load the dashboard index root.

### Known Inconsistencies
- **No Native Laravel Policies**: Role boundaries are hardcoded inside controller queries and custom middleware instead of using standard Laravel Policies (`app/Policies`) or Gates. This increases the risk of route leaks if query filters are missed in new features.
- **Manager Escalation Privilege**: There is no guard blocking a Manager from editing other Managers or Admins if they are mapped in the same department; hierarchy checks are split across individual queries.

### Future Improvements
- Migrate RBAC to Laravel Spatie Permission package or native Laravel policies/gates to standardize permission checks.
- Add granular audits logging who viewed or modified restricted employee profiles.

---

## 3. Data Encryption & Privacy Rules

### Intended Business Rule
To ensure compliance with data protection standards, sensitive personal identifying information (PII) — specifically Aadhaar card numbers, PAN card numbers, bank account numbers, and IFSC bank codes — must be encrypted at rest in the database and only decrypted dynamically during reads by authorized users.

### Current Implementation
- Mapped in the [EmployeeProfile](file:///c:/Users/Lenovo/AMS-V1/app/Models/EmployeeProfile.php#L65-L75) model using Laravel's built-in `encrypted` Eloquent casts.
- Under the hood, these casts use the `AES-256-CBC` cipher via Laravel's encrypter service, utilizing the `APP_KEY` defined in the `.env` configuration.
- If the `APP_KEY` changes, existing encrypted values in the database will be unreadable and throw decryption exceptions.

### Known Inconsistencies
- Database searches on encrypted columns (e.g., searching for a duplicate PAN or Aadhaar card) are impossible using basic SQL `WHERE` queries because the values are stored as distinct ciphertexts. Consequently, duplicate checks must pull all records and check them in memory, which does not scale.

### Future Improvements
- Introduce blind indexes (hashing columns with a secure salt) to allow secure search queries on encrypted fields without decrypting the entire database.

---

## 4. Related Modules & Cross References
- **[02_ATTENDANCE_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/02_ATTENDANCE_RULES.md)**: Restricts override capabilities to Admin roles.
- **[05_ORGANIZATION_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/05_ORGANIZATION_RULES.md)**: Governs user managers reporting lines mapping.
- **[08_MODULE_MAP.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/08_MODULE_MAP.md)**: Details files associated with Authentication and Profile settings.
