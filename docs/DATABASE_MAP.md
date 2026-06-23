# AMS-V1 — Database Schema & Model Maps

This document records the database schema, model attributes, relations, index structures, and sensitive column designations for AMS-V1.

---

## 1. Schema Diagram

```mermaid
erDiagram
    users {
        bigint id PK
        string employee_id UK "nullable"
        string name
        string email UK
        timestamp email_verified_at "nullable"
        string phone "nullable"
        string password
        enum role "admin, manager, employee"
        enum status "active, inactive, resigned"
        boolean must_change_password "default: true"
        date joining_date "nullable"
        decimal leave_balance "default: 0.00"
        bigint department_id FK "nullable"
        bigint manager_id FK "nullable"
        bigint admin_id FK "nullable"
        string remember_token "nullable"
        timestamp created_at
        timestamp updated_at
    }

    departments {
        bigint id PK
        string name
        string code UK "nullable"
        text description "nullable"
        timestamp created_at
        timestamp updated_at
    }

    users ||--o| users : "reports_to manager_id / admin_id"
    users ||--o| departments : "belongs_to department_id"
    users ||--|| employee_profiles : "1:1 profile user_id"
    attendances }o--|| users : "belongs_to user_id"
    leave_requests }o--|| users : "submitted_by user_id"
    leave_requests }o--|| users : "approved_by approver_id"
    leave_request_logs }o--|| leave_requests : "logs_changes_of leave_request_id"
    leave_request_logs }o--|| users : "action_taken_by user_id"
    leave_ledger_entries }o--|| users : "updates_balance_of user_id"
    leave_ledger_entries }o--o| leave_requests : "references_request leave_request_id"
    import_logs }o--|| users : "executed_by run_by_user_id"
    profile_correction_requests }o--|| users : "requested_by user_id"
    profile_correction_requests }o--o| users : "resolved_by resolved_by"
```

---

## 2. Table Definitions

### Table: `profile_correction_requests`
Stores employee profile edit requests, allowing HR to inspect and resolve details.

* **Columns:**
  * `id` (`bigint unsigned`, Primary Key, Auto Increment): Unique identifier.
  * `user_id` (`bigint unsigned`, Foreign Key -> `users.id`): Submitting employee.
  * `field` (`varchar(255)`): Profile database column identifier requested for change (e.g. `bank_name`).
  * `message` (`text`): Employee description and correction coordinates.
  * `status` (`varchar(255)`, Default: `'pending'`): Request status state (`pending`, `resolved`).
  * `admin_note` (`text`, Nullable): Resolution notes typed by Administrator.
  * `resolved_by` (`bigint unsigned`, Nullable, Foreign Key -> `users.id`): HR Administrator executing resolution.
  * `resolved_at` (`timestamp`, Nullable): Resolution timestamp.
  * `created_at` / `updated_at` (`timestamp`): Database timestamps.

* **Indexes & Keys:**
  * `PRIMARY KEY (id)`
  * `FOREIGN KEY profile_correction_requests_user_id_foreign (user_id) REFERENCES users(id) ON DELETE CASCADE`
  * `FOREIGN KEY profile_correction_requests_resolved_by_foreign (resolved_by) REFERENCES users(id) ON DELETE SET NULL`

### Table: `import_logs`
Logs bulk data migration sheet executions, recording processing statistics and JSON validation errors.

* **Columns:**
  * `id` (`bigint unsigned`, Primary Key, Auto Increment): Unique identifier.
  * `filename` (`varchar(255)`): Name of the Excel spreadsheet file processed.
  * `run_by_user_id` (`bigint unsigned`, Nullable, Foreign Key -> `users.id`): Administrator executing the run.
  * `rows_processed` (`int`): Total records processed.
  * `created_count` (`int`): Newly provisioned staff count.
  * `updated_count` (`int`): Updated employee profiles count.
  * `error_count` (`int`): Number of skipped rows containing warnings.
  * `errors` (`json`, Nullable): Detailed warning array matching row indices to failure descriptions (e.g. invalid status, missing headers).
  * `created_at` / `updated_at` (`timestamp`): Database timestamps.

* **Indexes & Keys:**
  * `PRIMARY KEY (id)`
  * `FOREIGN KEY import_logs_run_by_user_id_foreign (run_by_user_id) REFERENCES users(id) ON DELETE SET NULL`

### Table: `leave_ledger_entries`
Stores transactional, double-entry adjustment records tracking employee leave balance changes.

* **Columns:**
  * `id` (`bigint unsigned`, Primary Key, Auto Increment): Unique identifier.
  * `user_id` (`bigint unsigned`, Foreign Key -> `users.id`): Target employee.
  * `leave_request_id` (`bigint unsigned`, Nullable, Foreign Key -> `leave_requests.id`): Associated request (null for accruals/initializations).
  * `amount` (`decimal(8,2)`): Credit (+ve) or debit (-ve) amount (e.g. `-1.00`).
  * `type` (`varchar(255)`): Transaction type (`opening_balance`, `accrual`, `deduction`, `refund`, `adjustment`).
  * `description` (`varchar(255)`, Nullable): Descriptive log message.
  * `created_at` / `updated_at` (`timestamp`): Database timestamps.

* **Indexes & Keys:**
  * `PRIMARY KEY (id)`
  * `FOREIGN KEY leave_ledger_entries_user_id_foreign (user_id) REFERENCES users(id) ON DELETE CASCADE`
  * `FOREIGN KEY leave_ledger_entries_leave_request_id_foreign (leave_request_id) REFERENCES leave_requests(id) ON DELETE CASCADE`

### Table: `leave_requests`
Stores employee leave request applications and approval status classifications.

* **Columns:**
  * `id` (`bigint unsigned`, Primary Key, Auto Increment): Unique identifier.
  * `user_id` (`bigint unsigned`, Foreign Key -> `users.id`): Submitting employee.
  * `leave_type` (`varchar(255)`, Nullable): Resolved type (`paid_leave`, `unpaid_leave`, `work_from_home` - nullable for employees on create).
  * `start_date` (`date`): Leave start date.
  * `end_date` (`date`): Leave end date.
  * `total_days` (`int unsigned`): Total booking duration.
  * `reason` (`text`): Employee description.
  * `status` (`varchar(255)`, Default: `'pending'`): Current state (`pending`, `approved`, `rejected`, `cancelled`).
  * `approver_id` (`bigint unsigned`, Nullable, Foreign Key -> `users.id`): Approving manager or administrator.
  * `approved_at` (`timestamp`, Nullable): Action timestamp.
  * `rejection_reason` (`text`, Nullable): Reason typed by supervisor.
  * `notes` (`text`, Nullable): Reviewer comments.
  * `created_at` / `updated_at` (`timestamp`): Database timestamps.

* **Indexes & Keys:**
  * `PRIMARY KEY (id)`
  * `FOREIGN KEY leave_requests_user_id_foreign (user_id) REFERENCES users(id) ON DELETE CASCADE`
  * `FOREIGN KEY leave_requests_approver_id_foreign (approver_id) REFERENCES users(id) ON DELETE SET NULL`

### Table: `leave_request_logs`
Logs the chronological audit trail of all actions performed on a leave request.

* **Columns:**
  * `id` (`bigint unsigned`, Primary Key): Unique row identifier.
  * `leave_request_id` (`bigint unsigned`, Foreign Key -> `leave_requests.id`): Monitored request.
  * `from_status` (`varchar(255)`, Nullable): Prior status state.
  * `to_status` (`varchar(255)`): Updated status state.
  * `action` (`varchar(255)`): Executed action code (e.g. `submit`, `approve_paid`, `approve_unpaid`, `reject`, `cancel`, `override`).
  * `notes` (`text`, Nullable): Auditor notes.
  * `user_id` (`bigint unsigned`, Foreign Key -> `users.id`): User executing the action.
  * `created_at` (`timestamp`): Timestamp of the audit event.

* **Indexes & Keys:**
  * `PRIMARY KEY (id)`
  * `FOREIGN KEY leave_request_logs_leave_request_id_foreign (leave_request_id) REFERENCES leave_requests(id) ON DELETE CASCADE`
  * `FOREIGN KEY leave_request_logs_user_id_foreign (user_id) REFERENCES users(id) ON DELETE CASCADE`

### Table: `attendances`
Tracks daily physical clock-in and clock-out logs and stores punctuality status flags.

* **Columns:**
  * `id` (`bigint unsigned`, Primary Key, Auto Increment): Unique identifier.
  * `user_id` (`bigint unsigned`, Foreign Key -> `users.id`): Links to the employee.
  * `date` (`date`): Calendar date of the workday (records are constrained to one per user per day).
  * `check_in_time` (`timestamp`, Nullable): Clock-in timestamp.
  * `check_out_time` (`timestamp`, Nullable): Clock-out timestamp.
  * `status` (`enum('present', 'absent', 'late', 'on_leave', 'wfh')`, Default: `'absent'`): Status assigned by the parser.
  * `created_at` / `updated_at` (`timestamp`): Database timestamps.

* **Indexes & Keys:**
  * `PRIMARY KEY (id)`
  * `UNIQUE KEY attendances_user_id_date_unique (user_id, date)` (prevents double clock-in records)
  * `FOREIGN KEY attendances_user_id_foreign (user_id) REFERENCES users(id) ON DELETE CASCADE`

### Table: `employee_profiles`
Stores extended personal, emergency, address, education, experience, and banking details.

* **Columns:**
  * `id` (`bigint unsigned`, Primary Key, Auto Increment): Unique identifier.
  * `user_id` (`bigint unsigned`, Unique, Foreign Key -> `users.id`): 1:1 user mapping.
  * Personal info: `father_name`, `mother_name` (`varchar(255)`), `gender` (`varchar(255)`), `date_of_birth` (`date`), `marital_status` (`varchar(100)`), `date_of_marriage` (`date`), `nationality`, `blood_group` (`varchar(255)`), `personal_email` (`varchar(255)`), `mobile_no` (`varchar(255)`).
  * Professional parameters: `pf_uan`, `passport_no` (`varchar(255)`), `aadhar_card` (`text`, casted: `encrypted`), `pan` (`text`, casted: `encrypted`), `pf_no`, `esi_number` (`varchar(255)`), `date_of_gratuity` (`date`), `payroll_type` (`varchar(255)`), `contract_end_date` (`date`), `office_landline`, `leave_rule`, `shift`, `designation`, `grade`, `employee_type`, `company`, `location`, `biometric_id`, `hiring_source`, `source_of_verification` (`varchar(255)`).
  * Address fields: `current_address1`, `current_address2`, `current_country`, `current_state`, `current_city`, `current_zip` (`varchar(255)`), `same_as_current_address` (`tinyint(1)`), `permanent_address1`, `permanent_address2`, `permanent_country`, `permanent_state`, `permanent_city`, `permanent_zip` (`varchar(255)`).
  * Banking information: `payment_type`, `bank_name`, `account_holder_name` (`varchar(255)`), `account_no` (`text`, casted: `encrypted`), `ifsc_code` (`text`, casted: `encrypted`).
  * Emergency contacts: `emergency_name`, `emergency_relationship`, `emergency_address`, `emergency_email`, `emergency_mobile` (`varchar(255)`).
  * Education: `degree_name`, `institution_name`, `passing_year`, `percentage` (`varchar(255)`).
  * Experience: `previous_company_name`, `previous_job_title` (`varchar(255)`), `previous_from_date`, `previous_to_date` (`date`), `state_name`, `probation_period` (`varchar(255)`), `probation_confirm_date`, `separation_date`, `last_working_day` (`date`), `previous_year_experience`, `years_completed`, `overall_year_experience` (`varchar(255)` - refactored from float).
  * `notice_days` (`int`, Nullable), `joining_date` (`date`, Nullable).
  * `created_at` / `updated_at` (`timestamp`): Database timestamps.

* **Indexes & Keys:**
  * `PRIMARY KEY (id)`
  * `UNIQUE KEY employee_profiles_user_id_unique (user_id)`
  * `FOREIGN KEY employee_profiles_user_id_foreign (user_id) REFERENCES users(id) ON DELETE CASCADE`

### Table: `departments`
Groups employees into business units to structure queries and gate access.

* **Columns:**
  * `id` (`bigint unsigned`, Primary Key, Auto Increment): Unique identifier.
  * `name` (`varchar(255)`): Friendly department name (e.g. `Engineering`).
  * `code` (`varchar(10)`, Unique, Nullable): Short identifier code (e.g. `ENG`).
  * `description` (`text`, Nullable): Business scope notes.
  * `created_at` / `updated_at` (`timestamp`): Database timestamps.

* **Indexes & Keys:**
  * `PRIMARY KEY (id)`
  * `UNIQUE KEY departments_code_unique (code)`

### Table: `users`
Tracks employee login credentials, role assignments, system statuses, and reporting hierarchies.

* **Columns:**
  * `id` (`bigint unsigned`, Primary Key, Auto Increment): Unique identifier.
  * `employee_id` (`varchar(255)`, Unique, Nullable): Standardized employee code (e.g. `EMP00010`).
  * `name` (`varchar(255)`): Employee full name.
  * `email` (`varchar(255)`, Unique): Official corporate email address.
  * `email_verified_at` (`timestamp`, Nullable): Verification timestamp.
  * `phone` (`varchar(255)`, Nullable): Mobile contact number.
  * `password` (`varchar(255)`): BCRYPT-hashed credentials.
  * `role` (`enum('admin', 'manager', 'employee')`, Default: `'employee'`): Functional permission group.
  * `status` (`enum('active', 'inactive', 'resigned')`, Default: `'active'`): Employee lifecycle state.
  * `must_change_password` (`tinyint(1)`, Default: `1`): Flag forcing user to reset password upon login.
  * `joining_date` (`date`, Nullable): Employment start date.
  * `leave_balance` (`decimal(8,2)`, Default: `0.00`): Accrued leave days available.
  * `department_id` (`bigint unsigned`, Nullable, Foreign Key -> `departments.id`): Business unit reference.
  * `manager_id` (`bigint unsigned`, Nullable, Foreign Key -> `users.id`): Reporting manager.
  * `admin_id` (`bigint unsigned`, Nullable, Foreign Key -> `users.id`): HR Administrator reference.
  * `remember_token` (`varchar(100)`, Nullable): Session token.
  * `created_at` / `updated_at` (`timestamp`): Database timestamps.

* **Indexes & Keys:**
  * `PRIMARY KEY (id)`
  * `UNIQUE KEY users_email_unique (email)`
  * `UNIQUE KEY users_employee_id_unique (employee_id)`
  * `FOREIGN KEY users_department_id_foreign (department_id) REFERENCES departments(id) ON DELETE SET NULL`
  * `FOREIGN KEY users_manager_id_foreign (manager_id) REFERENCES users(id) ON DELETE SET NULL`
  * `FOREIGN KEY users_admin_id_foreign (admin_id) REFERENCES users(id) ON DELETE SET NULL`

---

## 3. Sensitive & Encrypted Fields
No sensitive columns are stored directly in the `users` table. Instead, financial and identification keys are isolated in the 1:1 mapped `employee_profiles` table and casted as `encrypted` in Eloquent:

* `employee_profiles.aadhar_card` (Aadhaar number)
* `employee_profiles.pan` (PAN card ID)
* `employee_profiles.account_no` (Bank account number)
* `employee_profiles.ifsc_code` (Bank IFSC routing code)

These fields are encrypted using Laravel's standard AES-256-CBC encryption cipher, using the application-wide `APP_KEY`. They are automatically decrypted when accessed via model properties and encrypted when saved to the database.

---

## 4. Framework Infrastructure

In addition to custom business application tables, AMS-V1 utilizes Laravel's default boilerplate tables to manage sessions, configuration caching, pessimistic resource locks, and background job queues:

### Table: `sessions`
Stores session payloads for authenticated users, tracking active connections, IP addresses, user agent details, and idle timeouts:
* `id` (`varchar(255)`, Primary Key): Unique session token.
* `user_id` (`bigint unsigned`, Nullable, Foreign Key -> `users.id`): References the logged-in user.
* `ip_address` (`varchar(45)`, Nullable): Client machine IP address.
* `user_agent` (`text`, Nullable): Browser platform metadata.
* `payload` (`longtext`): Encoded session state variables.
* `last_activity` (`integer`): UNIX timestamp of the user's last action.

### Tables: `cache` & `cache_locks`
Used by Laravel's database cache driver to store application state variables and serialise resources:
* **`cache`**: Stores transient key-value pairs (`key`, `value`, `expiration`).
* **`cache_locks`**: Used to orchestrate atomic locks across tasks, preventing concurrent executions (`key`, `owner`, `expiration`).

### Tables: `jobs`, `job_batches`, & `failed_jobs`
Orchestrates task queues, asynchronous mailer events, and Excel import reports:
* **`jobs`**: Holds queued tasks waiting for consumer dispatch (`queue`, `payload`, `attempts`, `reserved_at`, `available_at`, `created_at`).
* **`job_batches`**: Manages batches of parallel jobs, monitoring progress and errors (`name`, `total_jobs`, `pending_jobs`, `failed_jobs`, `failed_job_ids`, `options`, `cancelled_at`, `finished_at`).
* **`failed_jobs`**: Audits and stores failed queue jobs for developer diagnostics (`connection`, `queue`, `payload`, `exception`, `failed_at`).

---

## 5. Database Engine Parity

AMS-V1 maintains database engine parity between development, testing, and production environments:

* **Local/Testing Environment (SQLite):**
  * Configured in `.env` or `phpunit.xml` as `DB_CONNECTION=sqlite`.
  * Runs tests in-memory (`DB_DATABASE=:memory:`) to speed up execution.
  * SQLite does not enforce foreign keys by default, but Laravel's test setup configures foreign key checks during migration commands.
* **Production Environment (MySQL 8.0):**
  * Configured on Hostinger Linux Shared servers.
  * Enforces transactional safety, foreign key cascades, unique indices, and supports optimistic/pessimistic row locking (`lockForUpdate()`).
  * Migrations are executed with `--force` flags to update production tables without user prompts.
