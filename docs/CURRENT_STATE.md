# AMS-V1 — Current State Snapshot

This document provides a complete operational snapshot of the Attendance Management System Version 1 (AMS-V1) active release variables, implemented features, codebase health metrics, known limitations, and future priorities.

---

## 1. System Metadata & Snapshot

* **Current Version:** `v1.2-phase-5.8`
* **Current Phase:** Phase 5.8 — Visual Refinement & Complete Design Overhaul (Completed)
* **Latest Release Tag:** `v1.2-phase-5.8`
* **Current Branch:** `main`
* **Latest Commit:** `9971257630271a6897d6299a2469e1f46047c214` (Phase 5.7 merge; Phase 5.8 visual updates pending staging release commit)

---

## 2. Codebase Health & Test Metrics

* **Automated Test Suite:** SQLite in-memory Pest/PHPUnit tests configuration.
* **Test Status:** **100% PASS** (130 tests, 692 assertions verified).
* **Code Coverage:** Full coverage across leave ledger transactions, birthday credit grants, supervisor RBAC scopes, Excel uploader hierarchy logic, profile correction queues, attendance V3 overrides, healthcare shift timings, and bulk overrides conflict validations.

---

## 3. Implemented Features & Completed Phases

### A. Completed Phases
- **Phase C.1:** Employee & Department CRUD structures.
- **Phase B:** Sticky left sidebar navigation.
- **Phase C:** Clock-in/out endpoints and late delay calculation foundation.
- **Phase D:** Reporting managers self-referencing hierarchy.
- **Phase E:** Leave request status chains and approvals.
- **Phase 4.0 - 4.4:** Tabbed Employee Dossier with AES-256 encrypted fields (Aadhaar, PAN, Bank Details), text experience columns correction, uploader engine, correction requests, and logs center.
- **Phase 4.5 - 4.7.3:** Leave balance ledger, concurrency controls (`lockForUpdate`), nullable leave types, reusable credits engine, birthday leave credits sync, and readability passes.
- **Phase 4.8 - 4.9:** Standardized `<x-ledger-table>` components, Employee Dossier cleanup, and production configuration hardening (env cached compatibility).
- **Phase 5.0:** Attendance Engine V2 (database-driven department shift policies).
- **Phase 5.3:** Attendance Administration V3 (Alpine-driven tab workspace for Daily Roster, Override Management, and Audit Trail).
- **Phase 5.6:** Healthcare Department Shift Overrides & Birthday Leave Credit parameters sync.
- **Phase 5.7:** Bulk Attendance Overrides Workspace, preview capabilities, and conflict handling filters (`skip`, `replace`, `cancel`).
- **Phase 5.8:** Complete visual redesign (tactile Walnut/Ivory/Brass aesthetic, card grid separators, premium tables, 12-hour AM/PM clocks).

### B. Core Capabilities
- **Clock Engine**: Supports dynamic grace periods, late delays calculations, weekend exclusions, and approved leaves integration.
- **Bulk Override Workspace**: Allows Admin to preview and commit overrides for individual users, whole departments, or all active staff over single/range/multiple dates with conflict resolution options.
- **Double-Entry Leave Ledger**: Enforces transactional balance safety with database row-level locking, logging every credit or debit change as a ledger row.
- **Zimyo Import Engine**: Bulk imports workers from spreadsheets in two passes, auto-creating departments and mapping manager relationships without order restrictions.

---

## 4. Known Limitations & Technical Debt

### A. Known Limitations
1. **Employee ID Auto-Increment Collision Limit**: Sequential generation (`EMP00010`) queries the maximum ID prefix and increments it. A database unique constraint catches collisions under millisecond concurrent saves.
2. **Encryption Search Constraints**: Encrypted Aadhaar, PAN, and Bank details cannot be queried using database-level `LIKE` or sorted at database-level.
3. **No Direct Document Uploads**: File uploads (images, PDFs) are not supported. HR must manually copy numbers into profile forms.

### B. Technical Debt
1. **Cascade Hard Deletes on Employees**: Deleting an employee executes a database-level cascade, deleting historical attendance logs and ledger logs.
   - *Mitigation Plan:* Implement Soft Deletes (`use SoftDeletes`) in a future sprint to preserve financial and audit timelines.
2. **No Native Laravel Policies**: RBAC is hardcoded inside controller queries and custom middleware classes instead of Laravel Policies.
   - *Mitigation Plan:* Refactor RBAC to Laravel Spatie Permission package or native Policies/Gates in a stabilization sprint.

---

## 5. Operations & Priorities Roadmap

* **Active Priorities:**
  1. Maintain 100% test coverage and security alignment on all upcoming modules.
  2. Implement Phase 6 — Payroll Integration algorithms (calculate unpaid hours and salary deductions).
* **Next Recommended Phase:** **Phase 6 — Payroll Integration**.
