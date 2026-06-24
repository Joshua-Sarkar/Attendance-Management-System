# AMS-V1 — Current State

This document represents the live, production-ready operational state of the Attendance Management System Version 1 (AMS-V1) as of **June 24, 2026**.

---

## 1. System Metadata

* **Current Version:** `v1.2-phase-4.7.3`
* **Latest Functional Tag:** `v1.2-phase-4.7.3` (completed readability and usability enhancements)
* **Documentation Baseline Tag:** `v1.2-docs-baseline` (pointing to commit `e00d32b` / baseline refinements)
* **Latest Commit:** `716bd4a` (`docs(release): reconcile metadata, build Phase 4.8 readiness report, and prepare tag`)
* **Current Branch:** `main`
* **Production Environment:** Hostinger Linux Shared Server (cPanel setup)
* **Production Database Engine:** MySQL 8.0 (utilizing transactional row locks; local runs use SQLite in-memory)
* **Last Deployment Date:** June 24, 2026
* **Last Database Migration Executed:** `2026_06_24_154400_add_leave_credit_id_to_leave_requests.php` (adds relation from leave requests to the new reusable leave credits database)

---

## 2. Active Application Modules

### 1. Authentication & Security
* Laravel Breeze session-based authentication.
* Custom middleware checks ensuring new or password-reset employees are blocked and redirected to the `/password-change` route on login.
* Model-level encryption casts for sensitive financial and governmental fields (`aadhar_card`, `pan`, `account_no`, `ifsc_code`).

### 2. Department & Employee Directory
* Department CRUD interface with unique codes and descriptions.
* Extended personnel profile tabs with personal, contact, address, bank, education, and previous employment sections.
* Automated unique employee ID generator.

### 3. Attendance Tracking & Auditing
* Interactive dashboard check-in and check-out tracking buttons.
* Automated delay minute calculations (shift start 09:00 with a 15-minute grace period under old rules, or 09:30 with 15-minute grace under new rules).
* Weekend skips (Sundays are flagged as `weekend`, Saturdays are processed as workdays).
* Approved leaves and WFH integrations (approved requests set daily status to `on_leave` or `wfh` automatically; physical check-ins override leave).
* **Punctuality Audit Center:** Date, department, status, and search filters for check-in logs, alongside late arrivals and delay averages.

### 4. Leave Management & Balance Ledger
* Planned, Unplanned, and Birthday Leave categories (replaces legacy Paid/Unpaid selections).
* Approval status is the sole determinant of compensation outcome. Approved leaves deduct balance and protect salary (marked as `on_leave`). Rejected or cancelled leaves with no check-in default to `absent` (salary deducted). Physical check-ins override leave request statuses.
* **Reusable Leave Credit Engine:** Extensible ledger supporting allocation, tracking, consumption, and expiration of special leave credits (like Birthday Leave).
* **Birthday Leave Credits Sync:** Dynamic credits syncer unlocking birthday leave on Birthday - 1 day, automatically expiring unused credits on Birthday + 12 months, and supporting leap year (Feb 29) birthday fallbacks to Feb 28.
* Transaction audit ledger (`leave_ledger_entries` records all adjustments: opening balances, accruals, deductions, refunds).
* Concurrency checks via `lockForUpdate()` database row-level locking on user tables and leave credits.

### 5. Zimyo Migration Engine
* Bulk Excel file importer using `PhpSpreadsheet` library.
* Auto-creates missing departments and maps reporting managers using in-memory lookups.
* Auto-assigns temporary passwords and sets the password update requirement.

### 6. Profile Correction Requests
* Employee profile modification request forms.
* Admin review queue and update resolution dashboard.
* Sidebar red count badge displaying pending requests.

---

## 3. Operational Capabilities by Role

### Administrators (HR Managers / System Admins)
* Full control over employee directories, departments, and correction requests.
* Ability to upload Zimyo sheets and inspect migration errors.
* Global attendance logs visibility, late metrics audit, and override capability for leave requests.

### Managers (Department Heads)
* Scoped visibility limited to assigned employees.
* Ability to view direct reports' daily attendance records.
* Ability to approve or reject leave requests for assigned employees.

### Employees (Workforce Members)
* Clock-in and clock-out self-service.
* Leave application submissions (Planned, Unplanned, or Birthday Leave) and balance checks.
* Personal dashboard tracking (attendance rates, on-time streaks, weekly work hours).
* Profile correction request submissions.

---

## 4. Current Quality & Codebase Health

* **Test Suite Status:** 100% green.
* **Test Count:** 102 feature tests, 546 assertions.
* **Open Issues:** None.

---

## 5. Development Timeline

* **Active Phase:** Phase 4.8 — Executive UI Overhaul (in progress).
* **Next Planned Phase:** **Phase 5 — Payroll Integration** (calculating monthly salary components based on clock-in records, late arrival logs, and unpaid leaves - Deferred).
