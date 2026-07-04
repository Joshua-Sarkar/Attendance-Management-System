# AMS-V1 — Handover & Developer Onboarding Guide

This document serves as the primary onboarding entry point for developers, maintainers, auditors, and AI assistants continuing the development of the Attendance Management System Version 1 (AMS-V1).

---

## 1. Project Overview & Business Goals

* **Project Name:** AMS-V1 (Attendance Management System Version 1)
* **Purpose:** A centralized Human Resource operating system to govern workforce profiles, daily clock logs, and leave requests.
* **Business Goals:**
  1. Own workforce personal and banking databases securely by encrypting sensitive identifiers.
  2. Implement strict punctuality logic (shift start times, buffer grace periods, late delays).
  3. Manage leave balance accounts using a double-entry ledger database pattern.
  4. Streamline initial data imports from external Zimyo exports.
  5. Provide administrators with a unified override workspace to preview and commit roster corrections.

---

## 2. Technology Stack

* **Core Framework:** Laravel 12.0
* **Programming Language:** PHP 8.2+
* **Database Engine:** MySQL 8.0 (local runs utilize SQLite in-memory engine)
* **Frontend Stack:** HTML5 Semantic Markup + Blade Templates + Alpine.js (for reactive tab and modal states) + Vanilla Javascript (active client-side clock ticker)
* **Styling Baseline:** Vanilla CSS + custom color tokens inside `app.css` (Walnut/Ivory/Brass aesthetic, tactile primary/secondary button states, separated KPI panels, high-contrast tables).
* **Asset Bundler:** Vite (compiled via `npm run build`)
* **Hosting Environment:** Linux Shared Host (Hostinger PHP 8.2 runtime with cPanel mappings)

---

## 3. Current Operational Snapshot

* **Current Version:** `v1.2-phase-5.8`
* **Current Branch:** `main`
* **Latest Commit:** `9971257630271a6897d6299a2469e1f46047c214` (Phase 5.7 merge; Phase 5.8 visual updates pending staging release commit)
* **Pest Test Suite Status:** **100% PASS** (130 tests, 692 assertions verified).
* **Production URL:** Managed via cPanel domain mappings.
* **Production Database:** MySQL 8.0.

---

## 4. Architectural Patterns & Core Services

### A. Service-Oriented Architecture (SOA)
All complex business calculations and transactional writes reside in the Service layer (`app/Services/`) to keep Controllers thin and ensure code reusability across HTTP requests, API routes, and scheduled Artisan console commands.

### B. Isolated Timing Resolver
[AttendanceTimingResolver](file:///c:/Users/Lenovo/AMS-V1/app/Services/AttendanceTimingResolver.php) is the single source of truth for resolving shift start/end times, grace boundaries, and weekend checks. Decoupled from heavy models to prevent circular dependency errors.

### C. Double-Entry Leave Ledger
Leave balance changes are transactionally recorded in the `leave_ledger_entries` table. The user's `leave_balance` in the `users` table is a cached summary of the ledger sum. Concurrency safety is enforced using database row locks (`lockForUpdate`).

### D. Two-Pass Import Engine
[EmployeeImportService](file:///c:/Users/Lenovo/AMS-V1/app/Services/EmployeeImportService.php) processes spreadsheets in two passes: Pass 1 inserts User, Profile, and opening balances. Pass 2 maps hierarchical supervisor relationships, preventing circular loops and dependency order crashes.

---

## 5. Subsystem Business Rules

### A. Daily Check-in & Delay Rules
* **Check-in/out**: Employees can check in once and check out once per day.
* **Tardiness**: Arrival after the resolved shift start time plus grace minutes classifies the day as `late` and assigns the `half_day` classification (by default `'late_arrival'`).
* **Insufficient Hours**: A checkout with less than 4.0 hours (unless overridden) updates the classification to `half_day` (reason `'insufficient_hours'`).
* **Weekly Off**: Sundays default to a non-working `weekly_off` status and are excluded from absenteeism and streak calculations.
* **Rule B (Approved Leave Overrides)**: Approved leave requests dynamically mark dates as `on_leave` or `wfh` if no check-in exists. A physical check-in overrides the approved leave, setting the status to `present` or `late`.

### B. Department Shift Policies
* **Dynamic Shifts**: Departments can specify custom shift start, shift end, and grace minutes.
* **Healthcare Shift**: Matched case-insensitively by name/code `healthcare` or code `hlt`. Hard-overridden in configurations to always resolve to **10:00 - 18:00** shift times with **5 minutes grace** buffer.

### C. Leave Request Management
* **Submission types**: Employees must select their leave type on submission: `planned`, `unplanned`, `complimentary`, or `unpaid`.
* **Paid vs Unpaid Logic**:
  * **Paid**: `planned` and `complimentary` (Birthday Leave) are Paid. Approved planned requests deduct regular balance; complimentary requests consume birthday credit instead.
  * **Unpaid**: `unpaid` and `unplanned` are Unpaid. They bypass balance checks and deductions, logging a `0.00` ledger entry. Used by Payroll to calculate salary deductions.
* **Auto-Approval**: Admins are auto-approved. Complimentary (Birthday Leave) requests are auto-approved for everyone if an active credit token exists.
* **Decision Overrides**: Admins can override any leave request decision (approving pending/rejected items or cancelling approved items), which transactionally adjusts balances and writes ledger updates.

### D. Birthday Leave Tokens
* **Credit sync**: Synced `1` day before birthday (configurable). Valid for `1` year.
* **Leap Year**: Feb 29 birthdays resolve to Feb 27 in non-leap years.
* **Tenure Constraint**: Credits cannot be generated for years preceding the employee's `joining_date`.
* **Deduction check**: Auto-approves a 1-day complimentary leave request, setting the credit as consumed (`used_amount = 1.00`) and logging a `0.00` ledger trail.

### E. Override Workspace & Conflict Handling
* **Bulk workspace**: Admins can search daily logs, view timelines, and apply overrides to multiple employees or departments across single/range/multiple dates.
* **Conflict options**:
  * `skip`: Conflict records (existing manual overrides or approved leaves) are skipped.
  * `replace`: Performs the override, adjusts the user's leave balance cache, and writes ledger corrections.
  * `cancel`: Aborts the entire transaction if any conflict is detected.
* **Exclusions**: Optional filters to skip active leaves, skip overrides, exclude weekends, or include Sundays.

---

## 6. How to Run Locally

### Prerequisites
* PHP 8.2+
* Composer
* Node.js & npm
* SQLite

### Local Setup
1. **Clone & Install Dependencies:**
   ```bash
   git clone <repo-url> ams-v1
   cd ams-v1
   composer install
   npm install
   ```
2. **Setup Configurations:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Verify .env database parameters:*
   ```env
   DB_CONNECTION=sqlite
   ```
3. **Database Seeds:**
   ```bash
   touch database/database.sqlite
   php artisan migrate --seed
   ```
4. **Compile Assets & Serve:**
   ```bash
   npm run build
   php artisan serve
   ```

---

## 7. How to Test & Deploy

### Running Tests
Execute the Pest verification suite to run the 130 tests:
```bash
vendor/bin/pest
```

### Production Deployment (Hostinger cPanel)
1. **Build locally**: Run `npm run build` and push build assets.
2. **SSH to Hostinger**: Navigate to deployment directory and pull the latest `main` branch.
3. **Optimized install**:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
4. **Force Migrations**:
   ```bash
   php artisan migrate --force
   ```
5. **Optimize Caches (CRITICAL):**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
   > [!IMPORTANT]
   > Direct calls to `env()` outside of files in `config/` resolve to `null` once the configuration cache is compiled. Application code must read environment parameters exclusively using the `config('key')` helper.

---

## 8. Extending Subsystem Modules

To add features or adjust behaviors, follow the **Canonical Documentation Workflow**:
1. **Update specifications first**: Modify the corresponding rule document under `docs/domain/`.
2. **Database Migrations**: Add column adjustments. Never alter historical columns without backups.
3. **Services & Models**: Write calculations in the service layer, keeping models encapsulated.
4. **Controllers & Middleware**: Add parameter validation and RBAC checks.
5. **Views (Blade)**: Adapt UI elements following the specs in [07_UI_GUIDELINES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/07_UI_GUIDELINES.md).
6. **Tests**: Add Pest coverage verifying success paths and boundary limits.
7. **Synchronize Maps**: Update [08_MODULE_MAP.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/08_MODULE_MAP.md) and [10_CHANGE_GUIDE.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/10_CHANGE_GUIDE.md).

---

## 9. Current Risks & Future Roadmap

### A. Known Risks
* **ID generation collision**: Incrementing maximum sequential employee ID code can raise collision errors if two creations happen at the exact same millisecond. Caught by database unique keys.
* **Cascade Hard Deletes**: Deleting a User account deletes historical check-in logs and ledger lines.
  - *Future Fix:* Migrate to Soft Deletes.
* **RBAC hardcoding**: Lack of standard Laravel policies increases the risk of role-leak regressions in new features.
  - *Future Fix:* Migrate permission scopes to Laravel Spatie Permission package or native Policies/Gates.

### B. Future Roadmap
1. **Phase 6 — Payroll Integration**: Calculate unpaid hours and unpaid leaves, generating downloadable payslips.
2. **Geofencing & Biometrics**: Link coordinates or biometric check devices via webhooks.
3. **Report Exporter**: Automated PDF export for audit logs.

---

## 10. AI Continuation Prompt
For downstream AI models continuing development:
```text
Please read the handover document at docs/HANDOVER.md to understand the current project state, layout paths, and development goals.

Then, review these files in order:
1. docs/CURRENT_STATE.md (to confirm active version snapshot and test metrics)
2. docs/TECHNICAL_MAP.md (to locate codebase modules and database tables)
3. docs/DEPLOYMENT_GUIDE.md (to review deployment cache optimization commands)

The current target task is to initiate Phase 6 — Payroll Integration. Review the handover roadmap and wait for further instructions.
```
