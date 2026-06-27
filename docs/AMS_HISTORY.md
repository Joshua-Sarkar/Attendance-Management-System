# AMS-V1 — Chronological Release Archaeology & History Chronicle

This document presents the complete historical chronology, requirements progression, database evolution logs, release maps, and annotated git tag indexes for the Attendance Management System Version 1 (AMS-V1).

---

## 1. Project Phase Map

The table below maps the development timeline, indicating completion commits, intermediate commits, release tags, and descriptions.

| Order | Phase | Completion Commit | Intermediate Commits | Release Tag Name | Description & Release Notes |
| :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | **Phase C.1** | `e37dd81` | `2cd6e03`, `a43098a`, `ab6f4bc` | `v1.0-phase-c.1` | **Employee & Department CRUD:** Seeds, migrations, controller structures, validation schemas, status flags, and lists for directories. |
| 2 | **Phase B** | `e6a324e` | None | `v1.0-phase-b` | **Stitch Sidebar Redesign:** Sticky left navigation panel containing role-based menu options. |
| 3 | **Phase C** | `9ddd786` | None | `v1.0-phase-c` | **Attendance Tracking Foundation:** Check-in/out endpoints, late delay calculation (shift start 09:00 with 15m grace), and personal logs. |
| 4 | **Phase D** | `14a6f80` | None | `v1.0-phase-d` | **Hierarchy & Workforce Management:** manager_id self-referential user keys; restricted manager views to direct reports. |
| 5 | **Phase E** | `125e72e` | None | `v1.0-phase-e` | **Leave Requests & Rule B Override:** Leave request status chains, manager approvals, and Rule B approved leave overrides. |
| 6 | **Phase 4.1** | `d88009b` | None | `v1.1-phase-4.1` | **Zimyo Migration Engine:** Excel upload parser with PhpSpreadsheet executing in two passes to resolve supervisor hierarchies. |
| 7 | **Phase 4** | `3369d64` | `8e8b593` | `v1.1-phase-4` | **Profiles & Encrypted Fields:** Secondary profiles table, tabbed forms, and model-level encryption casts for Aadhaar, PAN, and Bank columns. |
| 8 | **Phase 4.3** | `ea088c8` | None | `v1.1-phase-4.3` | **Experience Column Corrections:** Refactored experience columns to nullable strings to allow textual duration inputs. |
| 9 | **Phase 4.2** | `05df1a8` | None | `v1.1-phase-4.2` | **Correction Requests & Hardening:** Employee profile correction request forms and HR resolution dashboard. |
| 10 | **Phase 4.4** | `82fd54a` | None | `v1.1-phase-4.4` | **Punctuality Audit Center:** Admin log search console with late exceptions and average delay metrics. |
| 11 | **Phase 4.5** | `b599f5a` | None | `v1.1-phase-4.5` | **Leave Balance Ledger & Concurrency:** Double-entry ledger audit logs, console accrual tools, and pessimistic row locking (`lockForUpdate`). |
| 12 | **Phase 4.6** | `2385dbb` | `3d517c9`, `918ad86` | `v1.2-phase-4.6` | **Leave Workflow Simplification:** Nullable leave types, approval classification, and sidebar correction badge counters. |
| 13 | **Phase 4.7** | `e00d32b` | None | `v1.2-docs-baseline` | **Architecture Traceability & Consolidation:** Retrospective audit establishing documentation maps and ADR logs. |
| 14 | **Phase 4.7.2** | `70cd6cc` | None | `v1.2-phase-4.7.2` | **Leave Authorization System:** Reusable credits engine, planned/unplanned types, birthday credit sync, and approval-driven attendance. |
| 15 | **Phase 4.7.3** | `c5a0652` (Code) | `ff023b2`, `d88d3d4`, `89d9987`, `d8bc202` | `v1.2-phase-4.7.3` | **Readability & Usability Pass:** Standardized table padding, hovers, list alignments, and desaturated tags. Skinned inputs to dark theme. |
| 16 | **Phase 4.8** | `v1.2-phase-4.8.0` | `4f87741`, `60de647`, `0f9907b` | `v1.2-phase-4.8.0` | **Release Candidate UI Overhaul & Polish:** Standardized all 7 organizational registries to a unified `<x-ledger-table>` ledger grid. Deduplicated action triggers from the Employee Dossier header (relocating all controls to the right-hand summary card). Centered action buttons horizontally next to the title headings. Fixed local test temp file offsets. |
| 17 | **Phase 4.9** | `v1.2-phase-4.9.0` | None | `v1.2-phase-4.9.0` | **Documentation Synchronization & Production Config Refactor:** Refactored direct `env()` environment helper usages in application code to Laravel configuration values to ensure configuration caching compliance. Cleaned up `/env-test` temporary debugging routes. Synchronized all documentation files across `/docs` with identical version (`v1.2-phase-4.9.0`) and state, and aligned all tests to configuration-based overrides. |

---

## 2. Release Tag Commands

Run these git command lines to generate annotated git release tags mapping to specific phase wrap-ups:

```bash
# Phase C.1 (Employee and Department CRUD)
git tag -a v1.0-phase-c.1 e37dd81 -m "Phase C.1 complete - Employee and Department CRUD forms, seeds, and lists"

# Phase B (Stitch Sidebar Redesign)
git tag -a v1.0-phase-b e6a324e -m "Phase B complete - Glassmorphic sidebar navigation layout"

# Phase C (Attendance Tracking Foundation)
git tag -a v1.0-phase-c 9ddd786 -m "Phase C complete - Clock-in/out foundation and delay calculation logic"

# Phase D (Hierarchy & Workforce Management)
git tag -a v1.0-phase-d 14a6f80 -m "Phase D complete - Manager-employee mapping and query boundary partitions"

# Phase E (Leave Requests & Rule B Override)
# git tag -a v1.0-phase-e 125e72e -m "Phase E complete - Leave status transitions and Rule B override rules"

# Phase 4.1 (Zimyo Migration Engine)
git tag -a v1.1-phase-4.1 d88009b -m "Phase 4.1 complete - Zimyo migration excel uploader, manager matcher, and transaction safety"

# Phase 4 (Profiles & Encrypted Fields)
git tag -a v1.1-phase-4 3369d64 -m "Phase 4 complete - Employee profile tabs, encryption casts, and RBAC tests"

# Phase 4.3 (Experience Column Corrections)
git tag -a v1.1-phase-4.3 ea088c8 -m "Phase 4.3 complete - Experience duration fields format changed to nullable strings"

# Phase 4.2 (Correction Requests & Hardening)
git tag -a v1.1-phase-4.2 05df1a8 -m "Phase 4.2 complete - Correction request submission queue and transactional approval flow"

# Phase 4.4 (Punctuality Audit Center)
git tag -a v1.1-phase-4.4 82fd54a -m "Phase 4.4 complete - Admin attendance audit grids, delay statistics, and gold theme"

# Phase 4.5 (Leave Balance Ledger & Concurrency)
git tag -a v1.1-phase-4.5 b599f5a -m "Phase 4.5 complete - Leave ledger balances, console commands, and lockForUpdate concurrency locks"

# Phase 4.6 (Leave Workflow Simplification)
# git tag -a v1.2-phase-4.6 2385dbb -m "Phase 4.6 complete - Simplified leave workflows, nullable request classification, and sidebar count badges"

# Phase 4.7 (Architecture Traceability & Consolidation)
git tag -a v1.2-docs-baseline e00d32b -m "Phase 4.7 complete - Retrospective architecture audit and documentation consolidation baseline"

# Phase 4.7.2 (Leave Authorization System & Credits Engine)
git tag -a v1.2-phase-4.7.2 70cd6cc -m "Phase 4.7.2 complete - Reusable leave credits engine, planned/unplanned categories, birthday leave credits sync, and approval-driven attendance resolution"

# Phase 4.7.3 (Readability & Usability Pass)
git tag -a v1.2-phase-4.7.3 716bd4a -m "Phase 4.7.3 complete - Readability & Usability Pass"

# Phase 4.8 (Release Candidate UI Overhaul & Polish)
git tag -a v1.2-phase-4.8.0 -m "Phase 4.8 complete - Unified ledger grids across Workforce, Departments, Leaves, Attendance and Correction Requests registries, header action button vertical alignments, Employee Dossier cleanup, and test warnings remediation"

# Phase 4.9 (Documentation Synchronization & Production Config Refactor)
git tag -a v1.2-phase-4.9.0 -m "Phase 4.9 complete - Refactored env() usages to config() for configuration caching compliance, removed debug routes, and synchronized all documents across /docs directory to v1.2-phase-4.9.0"
```

---

## 3. Subsystem Lineage Chronicle

### Phase B — Stitch Sidebar Redesign
* **Business Problem:** Scattered navigation links in header, lacking mobile responsiveness.
* **Details:** Built a responsive sticky left navigation panel containing role-based menu options.
* **Files:** `sidebar.blade.php` [NEW], `app.blade.php` [MODIFY].

### Phase C — Attendance Tracking Foundation
* **Business Problem:** Lacked centralized login trackers and delays math.
* **Details:** Created daily check-ins logging table, clock endpoints, delay duration calculations (shift start 09:00 with 15m grace), and personal history logs.
* **Files:** `2026_06_10_000000_create_attendances_table.php` [NEW], [AttendanceController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/AttendanceController.php) [NEW], [AttendanceService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/AttendanceService.php) [NEW].

### Phase C.1 — Employee and Department CRUD
* **Business Problem:** Admin could not configure departments or register workforce users.
* **Details:** Added Department model CRUD, directory listing tables, status checks (`active`/`inactive`), and auto-generated unique employee IDs.
* **Files:** [DepartmentController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/DepartmentController.php) [NEW], [EmployeeController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/EmployeeController.php) [NEW].

### Phase D — Hierarchy & Workforce Management
* **Business Problem:** Managers could view staff lists of unrelated departments, violating data security boundaries.
* **Details:** Integrated manager hierarchy controls restricting queries to direct reports.
* **Files:** `2026_06_11_134500_add_admin_id_to_users_table.php` [NEW], [DashboardController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/DashboardController.php) [NEW].

### Phase E — Leave Requests & Rule B Override
* **Business Problem:** Staff on approved leaves were marked absent due to empty logs.
* **Details:** Developed leave request status chains, manager review controls, and Rule B approved leaves overriding absent flags.
* **Files:** `2026_06_11_153000_create_leave_requests_table.php` [NEW], [LeaveRequest.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/LeaveRequest.php) [NEW], [LeaveRequestController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/LeaveRequestController.php) [NEW].

### Phase 4 — Profiles & Encrypted Fields
* **Business Problem:** Storing identifiers (Aadhaar, PAN, Bank Details) in plain text violated data compliance.
* **Details:** Isolated personal metadata inside `employee_profiles` and casted fields using AES-256 models encryption.
* **Files:** `2026_06_18_093324_create_employee_profiles_table.php` [NEW], [EmployeeProfile.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/EmployeeProfile.php) [NEW].

### Phase 4.1 — Zimyo Migration Engine
* **Business Problem:** Manual migration of personnel lists from Zimyo sheets was slow and mapping manager loops failed if subordinate rows preceded manager rows.
* **Details:** Created Excel upload parser executing in two passes to link managers, write encrypted personal records, and generate logs.
* **Files:** `2026_06_18_193234_create_import_logs_table.php` [NEW], [EmployeeImportService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/EmployeeImportService.php) [NEW].

### Phase 4.2 — Correction Requests & Hardening
* **Business Problem:** Employees required a way to update errors in profile details without direct edit credentials.
* **Details:** Built correction requests submission form, admin control queue, and transaction processing.
* **Files:** `2026_06_19_090000_create_profile_correction_requests_table.php` [NEW], [ProfileCorrectionRequestController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/ProfileCorrectionRequestController.php) [NEW].

### Phase 4.3 — Experience Column Corrections
* **Business Problem:** Experience fields cast as decimals failed to import text duration strings (e.g. "5 Years 2 Months").
* **Details:** Migrated columns to nullable strings in database.
* **Files:** `2026_06_19_084725_change_experience_columns_to_strings_in_employee_profiles.php` [NEW].

### Phase 4.4 — Punctuality Audit Center
* **Business Problem:** Admins lacked a search-filtered audit view to monitor check-ins, exceptions, and delay metrics.
* **Details:** Created scrollable audit log grid, search filters, late exception listings, and delay average stats.
* **Files:** [AttendanceAuditController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/AttendanceAuditController.php) [NEW], `admin/attendance-logs.blade.php` [NEW].

### Phase 4.5 — Leave Balance Ledger & Concurrency
* **Business Problem:** Editing leave balances directly lacked change logs and concurrent clicks caused duplicate deductions.
* **Details:** Created double-entry ledger audits, console accruals scheduler, and pessimistic row locking (`lockForUpdate`).
* **Files:** `2026_06_23_000000_add_leave_balance_and_ledger_tables.php` [NEW], [LeaveLedgerEntry.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/LeaveLedgerEntry.php) [NEW], [AccrueLeavesCommand.php](file:///c:/Users/Lenovo/AMS-V1/app/Console/Commands/AccrueLeavesCommand.php) [NEW].

### Phase 4.6 — Leave Workflow Simplification & Badge Alerts
* **Business Problem:** Staff self-classifying leaves caused errors. Admins lacked sidebar indicators for pending correction requests.
* **Details:** Made `leave_type` nullable in submissions; employees submit dates/reasons only. Managers select Paid/Unpaid on approval. Added sidebar count alert badge.
* **Files:** `2026_06_23_184204_make_leave_type_nullable_in_leave_requests_table.php` [NEW], [LeaveRequestController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/LeaveRequestController.php) [MODIFY], [sidebar.blade.php](file:///c:/Users/Lenovo/AMS-V1/resources/views/components/sidebar.blade.php) [MODIFY].

### Phase 4.7 — Architecture Traceability & Consolidation
* **Business Problem:** Fragile documentation, unmapped database configurations, and missing tag checkpoints.
* **Details:** Executed database, codebase, and security audit, established feature/database/test maps and decision logs.
* **Files:** `FEATURE_MAP.md` [NEW], `DATABASE_MAP.md` [NEW], `DECISION_LOG.md` [NEW], `RELEASE_MAP.md` [NEW].

### Phase 4.7.2 — Leave Authorization System & Credits Engine
* **Business Problem:** Leaves lacked automated controls for special credits (e.g. Birthday Leaves) and attendance lacked a payroll source of truth.
* **Details:** Removed Paid/Unpaid workflow in requests, replacing them with Planned, Unplanned, and Birthday Leave categories. Setup approval-driven attendance resolution (`on_leave` vs `absent`). Built a reusable `leave_credits` engine for birthday credits sync and expirations.
* **Files:** `2026_06_24_154000_create_leave_credits_table.php` [NEW], [LeaveCredit.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/LeaveCredit.php) [NEW], [LeaveAuthorizationModelTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/LeaveAuthorizationModelTest.php) [NEW].

### Phase 4.7.3 — Readability & Usability Pass
* **Business Problem:** Spacing density, low-contrast tags, and light-mode remnants in inputs and profiles.
* **Details:** Standardized cell padding (`py-3.5 px-5`), row hovers (`bg-brass/[0.04]`), and desaturated tag styles. Re-skinned Breeze forms to dark-theme panel components.
* **Files:** `resources/css/app.css` [MODIFY], `tailwind.config.js` [MODIFY], `employees/create.blade.php` [MODIFY], `profile/edit.blade.php` [MODIFY].

### Phase 4.8 — Release Candidate UI Overhaul & Polish
* **Business Problem:** Registry listings had structural inconsistencies, action buttons had misalignments, and the Employee Dossier had redundant buttons.
* **Details:** Consolidated all registries to use a unified `<x-ledger-table>`. Relocated header action triggers in the Employee Dossier to the right-hand metadata summary card. Centered page action buttons. Fixed local test timezone temp file offsets.
* **Files:** [ledger-table.blade.php](file:///c:/Users/Lenovo/AMS-V1/resources/views/components/ledger-table.blade.php) [NEW], [show.blade.php](file:///c:/Users/Lenovo/AMS-V1/resources/views/employees/show.blade.php) [MODIFY], [index.blade.php](file:///c:/Users/Lenovo/AMS-V1/resources/views/employees/index.blade.php) [MODIFY], [app.css](file:///c:/Users/Lenovo/AMS-V1/resources/css/app.css) [MODIFY].

### Phase 4.9 — Documentation Synchronization & Production Config Refactor
* **Business Problem:** Invoking the `env()` helper outside of configuration files returns `null` when configuration is cached in production. Additionally, the documentation sets had divergent version identifiers and phase references.
* **Details:** Introduced `config/employees.php` configuration wrapper. Migrated all direct code references of `DEFAULT_EMPLOYEE_PASSWORD` to use `config()`. Refactored tests to use container-level configuration overrides. Removed temporary `/env-test` debugging routes. Audited and synchronized the entire `/docs` directory to version `v1.2-phase-4.9.0`.
* **Files:** [employees.php](file:///c:/Users/Lenovo/AMS-V1/config/employees.php) [NEW], [EmployeeController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/EmployeeController.php) [MODIFY], [EmployeeImportService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/EmployeeImportService.php) [MODIFY], [PasswordStrategySecurityTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/PasswordStrategySecurityTest.php) [MODIFY], [EmployeeProfileAccessTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/EmployeeProfileAccessTest.php) [MODIFY], [AttendanceVerificationTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/AttendanceVerificationTest.php) [MODIFY], [web.php](file:///c:/Users/Lenovo/AMS-V1/routes/web.php) [MODIFY].
