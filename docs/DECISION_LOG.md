# AMS-V1 — Architectural Decision Log

This log documents the design reviews, alternatives evaluated, trade-offs accepted, and consequences of critical technical choices made throughout the life of the AMS-V1 project.

---

## ADR 1: Onboarding Credential Enforcement (Forced Password Reset)

### Problem
When employees are bulk-imported via Excel (Zimyo Engine) or created manually by an Administrator, they are assigned a default system-wide temporary password (`DEFAULT_EMPLOYEE_PASSWORD`). Leaving these temporary credentials active without renewal exposes the personnel profile and bank data to immediate security compromise.

### Context
Laravel Breeze provides complete authentication scaffolding but contains no built-in mechanism to intercept active sessions, block standard dashboard routes, or force password renewals. We need a centralized, maintainable control point that:
1. Intercepts all web requests.
2. Checks user status flags.
3. Restricts page interactions without bloating individual controllers.
4. Bypasses the blocks for the password change request inputs and the logout triggers to prevent infinite redirection loops.

### Alternatives Considered
* **Option A: Controller-level Checks:** Inject a helper check into every controller action.
  * *Trade-off:* High maintenance overhead; extremely prone to dev oversight during future feature integrations.
* **Option B: Route Grouping Scopes:** Split all active routes into two groups: "Verified Reset" and "Unverified Reset".
  * *Trade-off:* Complex route organization, complicates clean REST resources, and leads to messy route definitions.
* **Option C: HTTP Middleware Interceptor (Chosen):** Create a single custom middleware class registered in the application container's main web middleware pipeline.

### Chosen Solution
Implement the `CheckPasswordChange` route middleware, registered globally on the `web` pipeline in [app.php](file:///c:/Users/Lenovo/AMS-V1/bootstrap/app.php). The middleware queries the `must_change_password` boolean attribute on the authenticated user model. If true, and the current route is not `password.change`, `password.change.update`, or `logout`, it redirects the user to `/password/change`.

### Consequences
* **Positive:** Complete global coverage. Any new controllers or routes integrated in the future are automatically protected without extra code.
* **Negative:** If developers write external API routes or endpoints under the web group, they will get redirected unless explicitly whitelisted in the middleware route-name checks.
* **Related Files:**
  * [CheckPasswordChange.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Middleware/CheckPasswordChange.php) (middleware)
  * [PasswordController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/Auth/PasswordController.php) (routes handler)
  * [app.php](file:///c:/Users/Lenovo/AMS-V1/bootstrap/app.php) (middleware registrar)
* **Related Release:** Phase D (`v1.0-phase-d` completion commit `14a6f80`)

---

## ADR 2: Sequential Alphanumeric Employee ID Auto-Generation

### Problem
Exposing internal auto-increment database primary keys (`users.id`) directly in views, URLs, or export files exposes business metrics (employee volume) and creates insecure direct object reference (IDOR) vulnerabilities. We need a standardized corporate identifier that is unique, sequential, and formatted for corporate accounting.

### Context
Manual keying of employee codes leads to duplicates, formatting inconsistencies (e.g., mixing `EMP-1`, `emp_01`, and `EMP00001`), and data import mapping failures. The system must automatically suggest a formatted ID on user creation while validating uniqueness.

### Alternatives Considered
* **Option A: UUIDs:** Use random 36-character identifiers (e.g. `d3b07384d113...`).
  * *Trade-off:* High security, but impossible for HR and payroll staff to communicate verbally or print on ID badges.
* **Option B: Manual Input Only:** Force administrators to type unique codes.
  * *Trade-off:* High risk of duplicate key exceptions and typing fatigue.
* **Option C: Sequential suger prefix mapping (Chosen):** suggestion of alphanumeric codes starting at `EMP00001`, incrementing based on the highest existing code in the database.

### Chosen Solution
Create a helper method `generateEmployeeId()` inside [EmployeeController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/EmployeeController.php) that queries the maximum existing `employee_id` in the database, extracts the numeric suffix, increments it, and formats the output with a left zero pad of size 5, pre-pended with the `EMP` token.

### Consequences
* **Positive:** Consistent formatting (`EMP00001` to `EMP99999`), zero manual overhead for administrators, easy alignment with Zimyo imports.
* **Negative:** Relies on retrieving the highest ID which could create a race condition if two admins click create at the exact same millisecond. Since the database unique index on `employee_id` is active, it throws a query exception rather than saving duplicates, making it safe.
* **Related Files:**
  * [EmployeeController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/EmployeeController.php) (suggestion and creation handler)
* **Related Release:** Phase C.1 (`v1.0-phase-c.1` completion commit `e37dd81`)

---

## ADR 3: Model-Level Encryption Casts for Sensitive Identification & Financial Columns

### Problem
Storing government identifiers (Aadhaar, PAN) and banking coordinates in plain text exposes employees to identify theft and places the company in legal violation of privacy regulations.

### Context
We must ensure that even if the raw database is leaked or accessed by direct database admins, sensitive credentials remain unreadable without the encryption key. However, the application code should easily query, read, and display these records in forms when authenticated HR staff requests them.

### Alternatives Considered
* **Option A: Database-level TDE (Transparent Data Encryption):** Use MySQL native encryption functions.
  * *Trade-off:* Database vendor lock-in; complicates migration between engines (e.g. SQLite for testing vs MySQL for prod).
* **Option B: Manual Crypt Facades in Controllers:** Explicitly invoke `Crypt::encrypt()` and `Crypt::decrypt()` on every controller save and read.
  * *Trade-off:* Highly repetitive, verbose code; high risk of developer error.
* **Option C: Eloquent Model Casts (Chosen):** Map target columns to the `encrypted` cast type in the model configuration array.

### Chosen Solution
Declare target columns (`aadhar_card`, `pan`, `account_no`, `ifsc_code`) in the `$casts` property array of [EmployeeProfile.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/EmployeeProfile.php) with the type `encrypted`.

### Consequences
* **Positive:** Automatic transparent handling. The database stores ciphertext strings (AES-256), but the application reads plain text naturally. SQLite local tests run seamlessly using the same casts.
* **Negative:** Encrypted fields cannot be searched using standard SQL `WHERE ... LIKE` queries. Sorting on these fields is also impossible. Since these are private IDs, searching and sorting on them is not a business requirement.
* **Related Files:**
  * [EmployeeProfile.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/EmployeeProfile.php) (implements casts)
* **Related Release:** Phase 4 (`v1.1-phase-4` completion commit `3369d64`)

---

## ADR 4: Alphanumeric String Schemas for Experience Fields

### Problem
In initial schema drafts, experience metrics (e.g. total previous experience, years completed) were mapped as numeric decimal fields. When importing Zimyo export spreadsheets, parsing text duration descriptions like `"3 Years 6 Months"` or `"4.5"` caused database query exceptions during database insertion.

### Context
Excel sheets exported from Zimyo and similar third-party services contain organic, non-standardized string representations of experience metrics. Forcing numeric conversion in the import script led to data truncation (e.g. loss of months) or import crashes.

### Alternatives Considered
* **Option A: Excel Text Parsers:** Write complex regex functions to convert `"3 Years 6 Months"` to float `3.5`.
  * *Trade-off:* Excel sheets contain many unpredictable string variations, causing continuous parsing failures and high code complexity.
* **Option B: Database Schema Migration to Strings (Chosen):** Change columns to nullable strings.

### Chosen Solution
Create database migration `2026_06_19_084725_change_experience_columns_to_strings_in_employee_profiles.php` that alters the `previous_year_experience`, `years_completed`, and `overall_year_experience` columns in `employee_profiles` table to `string` format.

### Consequences
* **Positive:** Safe uploader operations, zero import crashes due to experience formats, and direct preservation of original Zimyo sheet terminology.
* **Negative:** Sorting or math operations on experience duration cannot be run at the database layer (must be parsed in memory if needed).
* **Related Files:**
  * `database/migrations/2026_06_19_084725_change_experience_columns_to_strings_in_employee_profiles.php` (schema refactor)
* **Related Release:** Phase 4.3 (`v1.1-phase-4.3` completion commit `ea088c8`)

---

## ADR 5: Configurable Historical Shift Rules Transition Strategy (09:00 vs 09:30 Shift Start)

### Problem
When shift start rules change (e.g., from 09:00 AM start to 09:30 AM start), applying the new rule retroactively to historical logs will alter existing clock-in records, falsely changing employee attendance histories (e.g. changing past status from `late` to `present`). We must maintain the integrity of historical logs while enforcing the new rule going forward.

### Context
Laravel databases store clock-in times as raw timestamps. If the application dynamically calculates "late minutes" on the fly using a single static configuration value, historical reports will fluctuate whenever the config changes. We need a way to support rules transitions at a specific point in time.

### Alternatives Considered
* **Option A: Write late minutes into the database:** Save `late_minutes` as a static column on the `attendances` table at the moment of clock-in.
  * *Trade-off:* High redundancy; database fields can go out of sync if cleanups/corrections are applied, and does not support retroactive fixes if the grace duration itself needs correcting.
* **Option B: Date-threshold config mapping (Chosen):** Define a `new_rules_start_date` parameter in `config/attendance.php` and dynamically evaluate the date of the record.

### Chosen Solution
In the `Attendance` model, retrieve the `attendance.new_rules_start_date` configuration. If the record's `date` is greater than or equal to this threshold date, calculate delay minutes relative to the new 09:30 shift start time. If it is prior to the threshold, fallback to the old 09:00 shift start baseline.

### Consequences
* **Positive:** Complete protection of historical payroll and compliance records. Changes to future shift hours do not corrupt historical data.
* **Negative:** Requires mapping the configuration parameter `new_rules_start_date` in the `.env` file of all environments. If it is omitted, the model defaults to the historical fallback rules (09:00 AM start) to ensure safety.
* **Related Files:**
  * [Attendance.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/Attendance.php) (dynamic late minutes getter)
  * [attendance.php](file:///c:/Users/Lenovo/AMS-V1/config/attendance.php) (configuration settings)
* **Related Release:** Phase 4.5 (`v1.1-phase-4.5` completion commit `b599f5a`)

---

## ADR 6: Simplified Nullable Leave Requests (Leave Type Nullability)

### Problem
In early iterations, standard employees had to classify their own leave requests (Casual Leave, Sick Leave, Earned Leave) when applying. However, employees frequently selected types for which they had insufficient balance, or misclassified standard LOP leaves. This resulted in significant HR correction overhead and manual rollbacks.

### Context
We want standard employees to only be responsible for specifying *when* they need time off and *why*, while leaving classification decisions (Paid vs Unpaid LOP) to the managers and administrators during review.

### Alternatives Considered
* **Option A: Client-side Balance Checks:** Validate balances dynamically in Javascript on the employee form.
  * *Trade-off:* Does not prevent users from choosing types they are not eligible for (e.g. sick leaves vs casual leaves classification criteria are determined by company policies, not just balance count).
* **Option B: Leave Nullability & Manager Classification (Chosen):** Make `leave_type` nullable, remove dropdowns for standard employees, and enforce type assignment at approval.

### Chosen Solution
Create database migration `2026_06_23_184204_make_leave_type_nullable_in_leave_requests_table.php` which alters the `leave_requests.leave_type` column to nullable. Remove dropdown select inputs from employee Blade forms. In `LeaveRequestController.php`, require managers to specify `leave_type = 'paid_leave'` or `'unpaid_leave'` during approval.

### Consequences
* **Positive:** Drastically reduced HR management overhead, eliminates invalid employee self-bookings, and ensures every paid leave is explicitly reviewed and verified by a manager.
* **Negative:** Admins applying for leave themselves still require type definitions on creation (handled via standard auto-approval pathways in `LeaveRequestController@store`).
* **Related Files:**
  * `database/migrations/2026_06_23_184204_make_leave_type_nullable_in_leave_requests_table.php` (schema migration)
  * [LeaveRequestController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/LeaveRequestController.php) (actions updates)
  * `resources/views/leaves/create.blade.php` (Blade template form inputs)
* **Related Release:** Phase 4.6 (`v1.2-phase-4.6` completion commit `2385dbb`)

---

## ADR 7: Double-Entry Ledger Ledger for Leave Balances

### Problem
Storing user leave balance solely as a single mutable column on the `users` table makes it impossible to trace the balance's historical changes. If balance calculations are corrupted or edited directly by HR, there is no audit log to diagnose the discrepancy or verify whether accruals and refunds were applied.

### Context
We must ensure complete accountability. Every single change to an employee's leave balance (whether it is an opening credit, monthly accrual addition, manager approval deduction, cancellation refund, or administrative override adjustment) must be verified and logged with an unalterable audit trail.

### Alternatives Considered
* **Option A: Detailed User History Logs:** Log text descriptions in a separate generic activity log table.
  * *Trade-off:* activity tables are formatted as strings, making automated recalculation audits difficult.
* **Option B: Numeric Transaction Ledger (Chosen):** Model balance updates after banking ledgers, where the current balance represents the mathematical sum of all ledger entries.

### Chosen Solution
Create the `leave_ledger_entries` table. Whenever a balance update is performed, write a matching log row containing the `amount` of change, the `type` classification, and the corresponding user ID.

### Consequences
* **Positive:** Complete visibility and auditability. If a balance check fails, running `SELECT SUM(amount) FROM leave_ledger_entries WHERE user_id = ?` returns the precise current balance, allowing self-correcting audits.
* **Negative:** Requires double-write logic in the application (save the User's aggregate balance column and save the ledger entry), which must be wrapped in transactions to prevent inconsistencies.
* **Related Files:**
  * [LeaveLedgerEntry.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/LeaveLedgerEntry.php) (ledger model)
  * `database/migrations/2026_06_23_000000_add_leave_balance_and_ledger_tables.php` (table schema)
* **Related Release:** Phase 4.5 (`v1.1-phase-4.5` completion commit `b599f5a`)

---

## ADR 8: Pessimistic Row Locking (lockForUpdate) for Leave Balance Modifications

### Problem
In high-concurrency scenarios (such as a manager clicking "Approve Leave" multiple times in rapid succession, or two supervisors reviewing leaves for the same user concurrently), multiple threads execute the check-and-deduct logic. This creates race conditions where the system checks the balance, proceeds to deduct, but before the first thread commits, the second thread reads the old balance, resulting in double deductions or negative balances.

### Context
Standard Laravel Eloquent reads and writes are non-blocking. To prevent double-clicks or concurrent writes from bypassing validation bounds, we must enforce serialized updates at the database level.

### Alternatives Considered
* **Option A: Optimistic Locking:** Use a version column on the `users` table.
  * *Trade-off:* Throws exceptions back to the user, forcing them to retry manually. Less seamless for rapid clicks.
* **Option B: UI-level Button Disabling:** Disable buttons in Javascript after the first click.
  * *Trade-off:* Flawed security; does not prevent direct API command line hits or automated script executions.
* **Option C: Pessimistic Row-level Database Locking (Chosen):** Use `lockForUpdate()` during User query retrieval inside transactions.

### Chosen Solution
Wrap the approval, cancellation, and override controller logic in a database transaction (`DB::transaction()`). Query the user record using the query chain: `User::where('id', ...)->lockForUpdate()->firstOrFail()`. This locks the matching database row until the transaction commits or rolls back, forcing subsequent updates to block and wait.

### Consequences
* **Positive:** Complete protection against duplicate deductions and race conditions. Concurrency feature tests confirm zero balance bypass.
* **Related Files:**
  * [LeaveRequestController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/LeaveRequestController.php) (implements locks)
* **Related Release:** Phase 4.5 (`v1.1-phase-4.5` completion commit `b599f5a`)

---

## ADR 9: Two-Pass Manager Resolution on Bulk Imports

### Problem
When bulk-importing employee directories from external spreadsheets, managers and employees are parsed row-by-row. If an employee row lists a manager whose User record has not yet been created (because their row is further down the Excel sheet), linking the `manager_id` foreign key fails, causing import crashes or missing reporting structures.

### Context
We want to allow excel files to be uploaded in any order, without forcing administrators to sort worksheets so that supervisors are always listed above subordinates.

### Alternatives Considered
* **Option A: Roster sorting requirements:** Enforce HR staff to sort excel sheets prior to upload.
  * *Trade-off:* High user friction; manual sorting is prone to error and layout failures.
* **Option B: Staging Table buffer:** Load imports into temporary database tables, then map hierarchies via cron queries.
  * *Trade-off:* High codebase bloat, introduces database queue overhead, and slows down immediate admin feedback.
* **Option C: In-Memory Two-Pass Import parsing (Chosen):** Process uploader operations in two distinct loops inside a single database transaction.

### Chosen Solution
Implement the uploader script in two distinct passes:
1. **Pass 1:** Create or update all standard User records and `employee_profiles` rows (bypassing `manager_id` assignments). Build an in-memory lookup map of numeric employee codes to database user primary keys.
2. **Pass 2:** Re-scan the rows, extract manager codes (e.g. from parenthesized text strings like `John Doe (24)`), resolve supervisor database IDs from the in-memory map, and save the `manager_id` foreign keys.

### Consequences
* **Positive:** Complete order independence. Excel files are imported successfully regardless of row arrangements. All updates are wrapped in a single database transaction, rolling back everything if a critical parser failure occurs.
* **Negative:** Slightly increased memory consumption to hold in-memory user IDs maps during large uploader runs (e.g. thousands of rows). Since imports are rare and rosters are under 1000 employees, memory limits are not hit.
* **Related Files:**
  * [EmployeeImportService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/EmployeeImportService.php) (importer service loops)
* **Related Release:** Phase 4.1 (`v1.1-phase-4.1` completion commit `d88009b`)

---

## ADR 10: Profile Correction Requests instead of Direct Profile Editing

### Problem
Allowing employees to edit their own profile fields directly introduces data verification vulnerabilities. For example, an employee could change their bank account details or designation without any HR review, which could cause payroll errors or fraud.

### Context
We want employees to be able to request updates to their profiles, but we must route all changes through an administrative review gate before they are committed to the database.

### Alternatives Considered
* **Option A: Direct Edit with Admin Notifications:** Allow employees to edit their fields directly, but send email alerts to HR.
  * *Trade-off:* High vulnerability. The unverified data is already live, forcing HR to run manual rollbacks if a change is invalid.
* **Option B: Pending Profile Staging Columns:** Add duplicate nullable staging columns for every attribute on `employee_profiles` (e.g. `pending_bank_name`).
  * *Trade-off:* High schema bloat and redundant columns.
* **Option C: Correction Requests Table (Chosen):** Create a separate request transaction table routing all suggestions through an Admin queue.

### Chosen Solution
Create the `profile_correction_requests` table. Employees submit requests specifying the target field and the proposed new value. Admins inspect the queue, copy the verified changes to the employee profile, and mark requests as resolved.

### Consequences
* **Positive:** Complete security gate. Sensitive profile parameters can never be changed without explicit Admin approval. The request log preserves a permanent audit trail of who resolved each correction request.
* **Related Files:**
  * [ProfileCorrectionRequestController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/ProfileCorrectionRequestController.php) (routes submissions and reviews)
  * [ProfileCorrectionRequest.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/ProfileCorrectionRequest.php) (request model)
* **Related Release:** Phase 4.2 (`v1.1-phase-4.2` completion commit `05df1a8`)

---

## ADR 11: Database Parity Strategy (SQLite for Testing vs MySQL in Production)

### Problem
Executing unit and feature tests against physical MySQL databases is slow, requires local database installations on the developer's computer, and risks corrupting local seed databases during test suite execution.

### Context
We want tests to execute in seconds. However, the production server on Hostinger runs MySQL 8.0. We must ensure that SQL queries written for the application compile and function correctly across both database engines.

### Alternatives Considered
* **Option A: MySQL Docker containers locally:** Run tests against a local MySQL docker container.
  * *Trade-off:* Hostinger shared hosting environments do not support Docker. Setting up local Docker databases increases onboarding complexity for future developers.
* **Option B: Local SQLite with Production MySQL (Chosen):** Use Laravel's database abstraction layer to support SQLite for development runs and MySQL in production.

### Chosen Solution
Configure `phpunit.xml` to override database configurations to use SQLite (`DB_CONNECTION=sqlite`) and in-memory storage (`DB_DATABASE=:memory:`). Maintain strict standard Eloquent query syntax, avoiding raw MySQL dialec specific scripts.

### Consequences
* **Positive:** Fast test execution (e.g. 98 tests pass in ~30 seconds), zero dependency on local MySQL services, easy setups.
* **Negative:** MySQL specific database features (such as raw locks `lockForUpdate()`) are silently ignored or behave slightly differently under SQLite. Concurrency tests mock these actions or rely on database transaction assertions to verify safety.
* **Related Files:**
  * `phpunit.xml` (test configurations overrides)
  * `.env.example` (environment default templates)
* **Related Release:** Initial Laravel setup (`d8fcc07`)

---

## ADR 12: Standard Git Branch Strategy (Taxonomy & Mappings)

### Problem
Topic and feature branches (e.g., `ui-redesign`, `phase-d-leave-management`) diverged from `main` production lines during early development stages, making codebase history difficult to navigate.

### Context
We need a unified branching strategy that identifies legacy branches, isolates current release lines, and enforces standard hotfix/release playbooks going forward.

### Alternatives Considered
* **Option A: GitFlow:** Maintain strict `master`, `develop`, `feature/*`, `release/*`, `hotfix/*` structures.
  * *Trade-off:* Too complex for a small corporate system in early active phases.
* **Option B: Trunk-Based Development with Topic Documentation (Chosen):** Execute updates directly on `main` or merge topic branches quickly, while maintaining a clear taxonomy of historical branches.

### Chosen Solution
Define `main` as the single source of truth and deployment branch. Document legacy branches (`develop`, `master`, `ui-layout`, `ui-redesign`) as read-only historical branches, and require all future patches to branch from `main` using the prefix `hotfix/`.

### Consequences
* **Positive:** Simple release logic, clean linear git history, and transparent branch maps.
* **Related Files:**
  * [VERSIONING.md](file:///c:/Users/Lenovo/AMS-V1/docs/VERSIONING.md) (standards documentation)
* **Related Release:** Phase 4.7 (`v1.2-docs-baseline` tag)

---

## ADR 13: Application-Wide Timezone Configuration Locking (Asia/Kolkata)

### Problem
In initial deployment checks on Hostinger Linux Shared hosting, daily attendance records and clock-in delays math were recorded with inconsistent offsets. Because the remote web servers and database instances defaults to Coordinated Universal Time (UTC), standard check-in actions computed clock-in delays based on UTC, resulting in a 5.5-hour delay offset relative to Indian Standard Time (IST) and assigning incorrect attendance statuses (e.g. marking present employees as absent or late).

### Context
We must ensure complete temporal consistency for attendance audits and payroll computations:
1. Daily clock-in timelines operate exclusively under the IST timezone (UTC+05:30).
2. Local developer environments (which may use different OS settings) must behave identically to the production server.
3. Automated test assertions must validate timezone alignment on startup.

### Alternatives Considered
* **Option A: Query-level Conversion:** Apply timezone conversion offsets (e.g., `date_add(check_in_time, INTERVAL 330 MINUTE)`) in raw database queries.
  * *Trade-off:* High query complexity, engine-specific SQL commands (MySQL vs SQLite), prone to developer oversight.
* **Option B: Application-level Timezone locking (Chosen):** Set configuration variables in the core PHP container setup.

### Chosen Solution
In [config/app.php](file:///c:/Users/Lenovo/AMS-V1/config/app.php), configure the default timezone parameter strictly to `'Asia/Kolkata'`:
`'timezone' => env('APP_TIMEZONE', 'Asia/Kolkata')`
Additionally, write a dedicated regression check inside [TimezoneTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/TimezoneTest.php) to verify that `date_default_timezone_get()` resolves strictly to `Asia/Kolkata` under all test runner runs.

### Consequences
* **Positive:** Consistent delay calculations across all environments, clean database timestamps representation, and complete safety against varying server default values.
* **Related Files:**
  * [app.php](file:///c:/Users/Lenovo/AMS-V1/config/app.php) (application configuration)
  * [TimezoneTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/TimezoneTest.php) (verification suite)
* **Related Release:** Phase 4.7 (`v1.2-docs-baseline` tag)

