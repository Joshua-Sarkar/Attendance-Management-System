# AMS-V1 — Production Readiness Matrix & System Status

This document defines the current production readiness status of the Attendance Management System Version 1 (AMS-V1) core subsystems, highlighting completed features, planned tasks, future milestones, and active operational constraints.

---

## 1. Readiness Matrix

| Subsystem / Module | Completed | Planned | Future | Status |
| :--- | :--- | :--- | :--- | :--- |
| **Authentication & RBAC** | Breeze session management; `CheckPasswordChange` onboarding middleware; disabled self-registration; password strategy validation tests. | None. | Two-Factor Authentication (2FA); OAuth2 social login. | **PRODUCTION READY** |
| **Workforce Ledger** | Department & User registry listings unified under the `<x-ledger-table>` grid; role-based query boundaries; cascading database deletes. | None. | Bulk employee status transition options. | **PRODUCTION READY** |
| **Employee Dossier** | Nullable experience string formats; tabbed HR forms; model-level AES-256 field encryption casts (Aadhaar, PAN, Bank Details); header action cards. | Correction requests Admin validation dashboard. | PDF document scans upload (Aadhaar/PAN attachments). | **PRODUCTION READY** |
| **Attendance Tracking** | Clock-in/out endpoints; dynamic late arrivals delay calculation (09:00/09:30 shift rules transition); IST Timezone verification. | None. | Geofencing validation; biometric terminal integration. | **PRODUCTION READY** |
| **Attendance Audit Center** | Scrollable log history grids; search inputs filtering by name/date/department/status; exception lists and delay averages. | None. | Automated PDF attendance reports exporter. | **PRODUCTION READY** |
| **Leave Management** | Date/reason leave submission; approval-driven classification (Paid/Unpaid selected by supervisor); approval-driven attendance mapping. | None. | Multi-level supervisor approval hierarchy. | **PRODUCTION READY** |
| **Leave Balance Ledger** | Double-entry ledger logs (`leave_ledger_entries`); console accruals scheduler (`leaves:accrue`); pessimistic locks (`lockForUpdate`). | None. | Accrual rules custom override panel. | **PRODUCTION READY** |
| **Employee Import Engine** | Zimyo Excel sheets uploader executing in two passes to link managers, write encrypted profiles, and initialize opening balances. | None. | Real-time Zimyo API integration sync. | **PRODUCTION READY** |
| **Profile Corrections** | Employee correction request submission form; sidebar alert count badges notifying Admin of pending items. | Resolving panel UI polish. | Direct profile merge utility with visual diff. | **PRODUCTION READY** |
| **UI Design System** | Dark-theme Walnut/Ivory/Brass styling; horizontal ledger grids; centered header title action alignments; custom inputs skins. | None. | Client-side theme toggler (Dark / Light mode). | **PRODUCTION READY** |
| **Configuration & Hardening** | locked Asia/Kolkata timezone; `config/employees.php` config wrapper preventing production config caching failures. | Mailer and SMTP host verification rules. | Vault configuration integration (e.g. AWS Secrets Manager). | **PRODUCTION READY** |
| **Testing & Verification** | 116 passing Pest/PHPUnit tests (605 assertions) covering RBAC, timezone locks, uploader loops, encryption, ledger safety, and V3 overrides. | None. | End-to-end browser testing utilizing Laravel Dusk. | **PRODUCTION READY** |

---

## 2. Production Environment Details

* **Hosting Server:** Linux Shared Hosting (Hostinger)
* **PHP Engine:** PHP 8.2+
* **Database Engine:** MySQL 8.0
* **Asset Compiler:** Vite + Tailwind CSS v4.0 (PostCSS)
* **Application URL:** Managed via cPanel domain mappings.
* **Storage Symlink:** Public directory storage link initialized (`php artisan storage:link`).
* **Cache Management:** Production caching rules apply (`config:cache`, `route:cache`, `view:cache` executed in deployment steps).

---

## 3. Configuration & Hardening Standards

### A. env() Usage Ban Outside Configuration Files
To prevent production runtime errors when Laravel's configuration cache is compiled (`php artisan config:cache`), direct calls to the `env()` helper are prohibited in controllers, models, services, or blade files. 
* All environment variables must be defined inside Laravel's `config/` directory.
* The application code must read configuration values exclusively using the `config('key')` helper.
* *Example:* Use `config('employees.default_employee_password')` instead of `env('DEFAULT_EMPLOYEE_PASSWORD')`.

### B. Automated Regression Verification
The test suite includes dedicated test assertions to ensure timezone configuration, password reset policies, and uploader managers mapping maintain production readiness.

---

## 4. Known Limitations & Constraints

1. **Employee ID Auto-Increment Collision:**
   The `generateEmployeeId()` method retrieves the maximum existing ID string, extracts the numeric value, and increments it. If two administrators submit user creation requests at the exact same millisecond, a duplicate ID could be generated. This is protected by a database-level `unique` constraint on the `employee_id` column, which will throw a query exception rather than allowing duplicate IDs to be committed.
2. **Encryption Search Restrictions:**
   Because Aadhaar, PAN, and Bank details are encrypted at rest using AES-256, standard database-level queries (`LIKE` or sorting) cannot be run on these columns. Search and sort filters are restricted to non-sensitive fields (names, email, employee ID).
3. **No Direct Document Uploads:**
   Personnel documents cannot be uploaded as PDF or image files. HR administrators must manually key in the identification alphanumeric strings in the tabbed Employee Dossier forms.
