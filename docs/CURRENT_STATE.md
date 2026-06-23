# AMS-V1 — Current State

This document represents the live, production-ready operational state of the Attendance Management System Version 1 (AMS-V1) as of **June 23, 2026**.

---

## 1. System Metadata

* **Current Version:** `v1.2-phase-4.6`
* **Latest Tag:** `v1.2-phase-4.6` (pointing to commit `2385dbb`)
* **Latest Commit:** `918ad86` ("Phase 4.6 - Add PHP 8.1+ null safety checks for leave type rendering")
* **Current Branch:** `main`
* **Production Environment:** Hostinger Linux Shared Server (cPanel setup)
* **Production Database Engine:** MySQL 8.0 (utilizing transactional row locks; local runs use SQLite in-memory)
* **Last Deployment Date:** June 23, 2026
* **Last Database Migration Executed:** `2026_06_23_184204_make_leave_type_nullable_in_leave_requests_table.php` (makes `leave_type` nullable in the `leave_requests` table to support simplified employee workflows)

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
* Nullable leave type submissions (employees apply for leaves with only start/end dates and reason).
* Manager/Admin approval workflow (approved as **Paid Leave** or **Unpaid Leave**, or rejected).
* Transaction audit ledger (`leave_ledger_entries` records all adjustments: opening balances, accruals, deductions, refunds).
* Concurrency checks via `lockForUpdate()` database row-level locking on user tables.

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
* Ability to approve (Paid/Unpaid) or reject leave requests for assigned employees.

### Employees (Workforce Members)
* Clock-in and clock-out self-service.
* Leave application submissions and balance checks.
* Personal dashboard tracking (attendance rates, on-time streaks, weekly work hours).
* Profile correction request submissions.

---

## 4. Current Quality & Codebase Health

* **Test Suite Status:** 100% green.
* **Test Count:** 98 feature tests, 534 assertions.
* **Open Issues:** None.

---

## 5. Development Timeline

* **Active Phase:** Phase 4.6 (completed).
* **Next Planned Phase:** **Phase 5 — Payroll Integration** (calculating monthly salary components based on clock-in records, late arrival logs, and unpaid leaves).
