# AMS-V1 — Handover Document

This document serves as the primary entry point for developers, maintainers, auditors, and AI assistants onboarding onto the Attendance Management System Version 1 (AMS-V1) project.

---

## 1. Project Overview

* **Project Name:** AMS-V1 (Attendance Management System Version 1)
* **Purpose:** A centralized, web-based platform to govern workforce profiles, daily check-in logs, and leave requests.
* **Business Goals:**
  1. Own workforce personal and banking databases securely by encrypting sensitive records.
  2. Implement strict punctuality logic (shift start times, buffer grace periods, late delays).
  3. Manage leave balance accounts using a double-entry ledger database pattern.
  4. Streamline initial data imports from external exports (Zimyo platform compatibility).
* **Current Production Status:** Live in production on Hostinger Linux Shared Server. Codebase has all tests passing and is synchronized under git control.

---

## 2. Current Snapshot

* **Current Version:** `v1.2-phase-5.3`
* **Latest Release Tag:** `v1.2-phase-5.3`
* **Documentation Baseline Tag:** `v1.2-docs-baseline` (pointing to Phase 4.7 consolidation commit)
* **Current Branch:** `main`
* **Latest Commit:** `d89fb6f`
* **Production URL:** Hostinger production directory root (cPanel mappings active)
* **Production Database:** MySQL 8.0
* **Current Deployment Environment:** Linux Shared Web Server (Hostinger PHP 8.2 runtime)

---

## 3. Technology Stack

* **Laravel Version:** 12.0
* **PHP Version:** 8.2+ (verified with PHP 8.1+ null-safety string functions)
* **Database Engine:** MySQL 8.0 (local runs utilize SQLite in-memory engine)
* **Frontend Stack:** HTML5 Semantic Markup + Blade Layout Templates + Vanilla Javascript (active client-side clock ticker, tilt cards, and count-up loaders)
* **Styling Framework:** Tailwind CSS v4.0 (PostCSS compilation) augmented with custom glassmorphic panels and dark-gold color tokens in `app.css`
* **Hosting Environment:** Linux Shared Host (cPanel directory controls)

---

## 4. Active Modules

### 1. Authentication & RBAC
* **Purpose:** Authenticates users and isolates menus and record views based on roles (`admin`, `manager`, `employee`). Forces password changes on onboarding.
* **Current Status:** Operational. Public self-registration is disabled.
* **Primary Files:**
  * [CheckPasswordChange.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Middleware/CheckPasswordChange.php) (middleware)
  * [AuthenticatedSessionController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/Auth/AuthenticatedSessionController.php) (session controller)
  * [PasswordController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/Auth/PasswordController.php) (password change and provisioning updates)

### 2. Workforce Management
* **Purpose:** Manages department structures and creates, edits, or deletes employee listings.
* **Current Status:** Operational. Auto-generates unique Employee IDs. Index directories are fully aligned with Phase 4.7.3 contrast and vertical padding standards.
* **Primary Files:**
  * [EmployeeController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/EmployeeController.php)
  * [DepartmentController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/DepartmentController.php)

### 3. Employee Profiles
* **Purpose:** Stores personal details, emergency contacts, addresses, previous employment, and encrypted financial/government IDs (Aadhaar, PAN, Bank details).
* **Current Status:** Operational. Encrypts sensitive fields automatically at rest using model casts.
* **Primary Files:**
  * [EmployeeProfile.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/EmployeeProfile.php) (includes casts array)
  * Views folders under `resources/views/employees/`

### 4. Attendance Tracking
* **Purpose:** Logs employee check-in and check-out logs and calculates delay minutes relative to the grace threshold.
* **Current Status:** Operational. Late arrivals are processed dynamically using model properties.
* **Primary Files:**
  * [Attendance.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/Attendance.php) (includes late minutes logic)
  * [AttendanceService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/AttendanceService.php) (check-in/out logic)

### 5. Attendance Audit & Override Center
* **Purpose:** A centralized interface for Admins to search logs, view timelines, and apply individual or bulk overrides on status and classifications (Full/Half Day) with full audit log capabilities.
* **Current Status:** Operational. Fully database-driven department shift policies.
* **Primary Files:**
  * [AttendanceAuditController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/AttendanceAuditController.php) (read operations)
  * [AttendanceOverrideController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/AttendanceOverrideController.php) (write overrides)
  * `resources/views/admin/attendance-logs.blade.php` (UI modals)

### 6. Leave Management
* **Purpose:** Allows employees to submit leave requests. Handles approval workflows and cancels.
* **Current Status:** Operational. Leaves are submitted without type parameters; Paid/Unpaid classification is resolved at the approval stage.
* **Primary Files:**
  * [LeaveRequestController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/LeaveRequestController.php)
  * [LeaveRequest.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/LeaveRequest.php)

### 7. Leave Balance Ledger
* **Purpose:** Implements transactional, double-entry leave records.
* **Current Status:** Operational. Uses database pessimistic locks to prevent race conditions during updates.
* **Primary Files:**
  * [LeaveLedgerEntry.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/LeaveLedgerEntry.php) (ledger table model)
  * [LeaveBalanceService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/LeaveBalanceService.php)

### 8. Employee Import Engine
* **Purpose:** Parses Zimyo Excel exports, registers missing departments, maps hierarchies in two passes, and initializes opening balances.
* **Current Status:** Operational. Renders import metrics, log listings, and warnings in style compliance with Phase 4.7.3.
* **Primary Files:**
  * [EmployeeImportService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/EmployeeImportService.php)
  * [ImportController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/ImportController.php)

### 9. Profile Correction Requests
* **Purpose:** Allows employees to submit profile corrections, notifying Admins via a sidebar count badge.
* **Current Status:** Operational. Renders request log tables using high-contrast text and desaturated tag styles.
* **Primary Files:**
  * [ProfileCorrectionRequestController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/ProfileCorrectionRequestController.php)
  * [sidebar.blade.php](file:///c:/Users/Lenovo/AMS-V1/resources/views/components/sidebar.blade.php) (badge element)

### 10. Scheduled Commands
* **Purpose:** Console commands for backing up data and managing leave balance credits.
* **Current Status:** Operational.
* **Primary Files:**
  * [AccrueLeavesCommand.php](file:///c:/Users/Lenovo/AMS-V1/app/Console/Commands/AccrueLeavesCommand.php) (`leaves:accrue` adds 2 credits monthly, idempotent)
  * [InitializeBalancesCommand.php](file:///c:/Users/Lenovo/AMS-V1/app/Console/Commands/InitializeBalancesCommand.php) (`leaves:initialize-balances` configures initial credits)

---

## 5. Current Capabilities By Role

### Admin
* Full employee and department CRUD.
* Access to Zimyo excel files uploader.
* Override capabilities on leave requests.
* Queue view to review and resolve correction requests.
* Inspection access to global attendance logs and late metrics dashboards.

### Manager
* Ability to view direct reports' daily attendance records.
* Ability to approve (Paid/Unpaid) or reject leave requests for assigned employees.

### Employee
* Interactive check-in/out dashboard buttons.
* Personal calendars showing monthly attendance rates, streak details, and work hours.
* Leave request and cancellation submissions.
* Correction request submissions.

---

## 6. Current Database Summary

AMS-V1 uses 9 primary tables. Relationships are managed at the database level using foreign keys:

* `users`
* `departments` (each employee belongs to a department)
* `attendances` (each record represents a user check-in per day)
* `leave_requests` (linked to `users` with an option for approval details)
* `leave_request_logs` (logs request status changes)
* `employee_profiles` (1:1 extension mapping of the `users` table)
* `import_logs` (logs bulk user import details)
* `profile_correction_requests` (records employee edit requests)
* `leave_ledger_entries` (audit logs tracking leave balance modifications)

---

## 7. Current Known Issues

Currently, there are no known critical issues. The application has 100% test coverage with 116 tests (605 assertions) fully green.

---

* **Active Phase:** Sprint 2.3.1 — Repository Stabilization & Documentation Synchronization (Completed).
* **Last Completed Phase:** Sprint 2.3 — Attendance Administration V3 (v1.2-phase-5.3).
* **Next Planned Phase:** **Phase 5 — Payroll Integration**.

---

## 9. Immediate Next Priorities

1. **Phase 5 (Payroll Module):** Create calculation algorithms to determine unpaid hours based on check-in logs and unpaid leaves, mapping outputs into downloadable payslips.

---

## 10. Documentation Map

Refer to these documentation files inside the `/docs` directory:

1. [HANDOVER.md](file:///c:/Users/Lenovo/AMS-V1/docs/HANDOVER.md): The primary entry point for developers and AI continuity prompts.
2. [CURRENT_STATE.md](file:///c:/Users/Lenovo/AMS-V1/docs/CURRENT_STATE.md): Lightweight snapshot of version, commit, health, and priorities.
3. [TECHNICAL_MAP.md](file:///c:/Users/Lenovo/AMS-V1/docs/TECHNICAL_MAP.md): Unified codebase paths, database schemas, and Pest tests mapped by subsystem.
4. [DEPLOYMENT_GUIDE.md](file:///c:/Users/Lenovo/AMS-V1/docs/DEPLOYMENT_GUIDE.md): Setup scripts, cPanel deployment playbooks, backup tasks, SemVer rules, and rollback guides.
5. [AMS_HISTORY.md](file:///c:/Users/Lenovo/AMS-V1/docs/AMS_HISTORY.md): Narration of requirements evolution, project phase commits, and annotated release tags.
6. [UI_OVERHAUL_SPEC.md](file:///c:/Users/Lenovo/AMS-V1/docs/UI_OVERHAUL_SPEC.md): Consistency audits, component inventories, design debt items, and Phase 4.9 readiness parameters.
7. [DECISION_LOG.md](file:///c:/Users/Lenovo/AMS-V1/docs/DECISION_LOG.md): Immutable Architectural Decision Records (ADRs) logs database.
8. [GIT_STANDARDS.md](file:///c:/Users/Lenovo/AMS-V1/docs/GIT_STANDARDS.md): Conventional Commits formats, annotated tags criteria, and audit checklists.
9. [PRODUCTION_READINESS.md](file:///c:/Users/Lenovo/AMS-V1/docs/PRODUCTION_READINESS.md): Production readiness matrix, subsystem statuses (Completed/Planned/Future), environment specifications, and known limitations.

---

## 11. Recovery & Rollback Quick Guide

For the standard deploy routines, configuration cache optimizations, code releases checkout instructions, database rollbacks, and recovery snapshots workflows, refer directly to:
* [DEPLOYMENT_GUIDE.md: Section 2 (Hostinger Production Deployment Workflow)](file:///c:/Users/Lenovo/AMS-V1/docs/DEPLOYMENT_GUIDE.md#2-hostinger-production-deployment-workflow)
* [DEPLOYMENT_GUIDE.md: Section 6 (Rollback Procedures)](file:///c:/Users/Lenovo/AMS-V1/docs/DEPLOYMENT_GUIDE.md#6-rollback-procedures)
* [DEPLOYMENT_GUIDE.md: Section 7 (Emergency Recovery Procedures)](file:///c:/Users/Lenovo/AMS-V1/docs/DEPLOYMENT_GUIDE.md#7-emergency-recovery-procedures)

---

## 12. AI Continuation Prompt

To continue development in a new chat session, copy and paste this prompt:

```text
Please read the handover document at docs/HANDOVER.md to understand the current project state, layout paths, and development goals.

Then, review these files in order:
1. docs/CURRENT_STATE.md (to confirm active version snapshot and metadata)
2. docs/TECHNICAL_MAP.md (to locate codebase modules and database tables)
3. docs/DEPLOYMENT_GUIDE.md (to review deployment setup scripts)

Treat the project documentation inside `/docs` as the source of truth for the codebase.

The current target task is to initiate Phase 5 — Payroll Integration. Review the handover roadmap and wait for further instructions.
```
