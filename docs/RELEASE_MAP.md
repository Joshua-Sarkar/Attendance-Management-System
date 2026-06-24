# AMS-V1 — Release Map

This document presents a comprehensive release archaeology of the Attendance Management System Version 1 (AMS-V1), mapping the development milestones, exact git commits, tag annotations, and phase accomplishments chronologically.

---

## 1. Project Phase Mapping

The table below maps the development timeline, indicating completion commits, intermediate commits, recommended release tags, and notes.

| Chronological Order | Phase | Completion Commit | Intermediate Commits | Recommended Tag Name | Description & Release Notes |
| :--- | :--- | :--- | :--- | :--- | :--- |
| 1 | **Phase C.1** | `e37dd81` | `2cd6e03`, `a43098a`, `ab6f4bc` | `v1.0-phase-c.1` | **Employee & Department CRUD:** Implemented database seeds, migrations, controller structures, validation schemas, status flags, and lists for department directories and staff records. |
| 2 | **Phase B** | `e6a324e` | None | `v1.0-phase-b` | **Stitch Sidebar Redesign:** Integrated modern sticky left navigation panel containing role-based menu options. |
| 3 | **Phase C** | `9ddd786` | None | `v1.0-phase-c` | **Attendance Tracking Foundation:** Added check-in/out endpoints, late delay calculation logic (shift start 09:00 with 15m grace), and personal logs. |
| 4 | **Phase D** | `14a6f80` | None | `v1.0-phase-d` | **Hierarchy & Workforce Management:** Added `manager_id` self-referential user keys and restricted user logs access so managers only see assigned staff. |
| 5 | **Phase E** | `125e72e` | None | `v1.0-phase-e` | **Leave Requests & Rule B Override:** Built leave request status chains, manager approval paths, and Rule B logic where approved leaves override absent flags. *(Note: Tag `v1.0-phase-e` and `v1.0-production-ready` already exist in repository).* |
| 6 | **Phase 4.1** | `d88009b` | None | `v1.1-phase-4.1` | **Zimyo Migration Engine:** Created a bulk Excel file upload parsing engine utilizing `PhpSpreadsheet`, executing in two passes to link managers, write encrypted personal records, and generate logs. *(Note: Originally committed as "Phase 2").* |
| 7 | **Phase 4** | `3369d64` | `8e8b593` | `v1.1-phase-4` | **Profiles & Encrypted Fields:** Created the secondary profiles table, added tabbed forms, and implemented model-level encryption casts for Aadhaar, PAN, and Bank columns. |
| 8 | **Phase 4.3** | `ea088c8` | None | `v1.1-phase-4.3` | **Experience Column Corrections:** Refactored profile table fields (overall experience, years completed) from float fields to string formats to allow textual data imports. |
| 9 | **Phase 4.2** | `05df1a8` | None | `v1.1-phase-4.2` | **Correction Requests & Hardening:** Built the employee correction queue, admin control panel, and database transactions for profile updates. |
| 10 | **Phase 4.4** | `82fd54a` | None | `v1.1-phase-4.4` | **Punctuality Audit Center:** Built a search-filtered attendance list grid showing check-in timelines and average late arrival metrics, complete with dark-gold theme. |
| 11 | **Phase 4.5** | `b599f5a` | None | `v1.1-phase-4.5` | **Leave Balance Ledger & Concurrency:** Created transactional double-entry ledger audits, console accrual tools, and pessimistic row locking (`lockForUpdate`). |
| 12 | **Phase 4.6** | `2385dbb` | `3d517c9`, `918ad86` (Patch) | `v1.2-phase-4.6` | **Leave Workflow Simplification:** Made `leave_type` nullable on create. Removed dropdowns for employees and moved Paid/Unpaid selection to approval. Added sidebar badge counters. *(Note: Tag `v1.2-phase-4.6` already exists in repository).* |
| 13 | **Phase 4.7** | `e00d32b` | None | `v1.2-docs-baseline` | **Architecture Traceability & Consolidation:** Conducted retrospective architecture audit, verified and created all missing historical tags, established Feature/Database/Test maps and ADR Decision Logs. |
| 14 | **Phase 4.7.2** | `70cd6cc` | None | `v1.2-phase-4.7.2` | **Leave Authorization System:** Implemented reusable leave credits database schema and engine, planned/unplanned leave categories, birthday leave credits sync & auto-approvals, dynamic attendance status resolution (on_leave/absent) as sole source of truth for payroll impact, and tests. |
| 15 | **Phase 4.7.3** | `c5a0652` (Code) | `ff023b2`, `d88d3d4`, `89d9987`, `d8bc202` | `v1.2-phase-4.7.3` | **Readability & Usability Pass:** Harmonized table layout cell padding, hover effects, alignment rules, and status tag styles across leave requests, attendance history, correction queue, and excel import lists. Re-skinned Breeze form templates to corporate dark-theme panel components. |

---

## 2. Historical Git Tag Commands

Use the commands below to generate annotated historical git tags for older phase completions where tags were not applied during early stages of development.

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
# (Tag already exists in repository: v1.0-phase-e and v1.0-production-ready on 125e72e)
# If recreating:
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
# (Tag already exists in repository: v1.2-phase-4.6 on 2385dbb)
# If recreating:
# git tag -a v1.2-phase-4.6 2385dbb -m "Phase 4.6 complete - Simplified leave workflows, nullable request classification, and sidebar count badges"

# Phase 4.7 (Architecture Traceability & Consolidation)
git tag -a v1.2-docs-baseline -m "Phase 4.7 complete - Retrospective architecture audit and documentation consolidation baseline"

# Phase 4.7.2 (Leave Authorization System & Credits Engine)
git tag -a v1.2-phase-4.7.2 70cd6cc -m "Phase 4.7.2 complete - Reusable leave credits engine, planned/unplanned categories, birthday leave credits sync, and approval-driven attendance resolution"

git tag -a v1.2-phase-4.7.3 716bd4a -m "Phase 4.7.3 complete - Readability & Usability Pass"
```

---

## 3. Remote Push Directions

To sync all newly generated historical tags to your origin repository, run the following:

```bash
# Push specific new tag
git push origin v1.0-phase-c.1

# Push all tags
git push origin --tags
```
