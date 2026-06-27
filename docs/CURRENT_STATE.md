# AMS-V1 — Current State Snapshot

This document provides a lightweight operational snapshot of the Attendance Management System Version 1 (AMS-V1) active release variables.

---

## 1. System Metadata & Snapshot

* **Current version:** `v1.2-phase-4.9.0`
* **Current Phase:** Phase 4.9 — Documentation Synchronization & Production Config Refactor (Completed)
* **Latest Release Tag:** `v1.2-phase-4.9.0`
* **Current Branch:** `main`

---

## 2. Codebase Health & Test Metrics

* **Automated Test Suite:** SQLite in-memory Pest tests configuration.
* **Test Status:** **100% PASS** (103 tests, 548 assertions verified).
* **Code Coverage:** Full coverage across leave ledger transactions, birthday credit grants, supervisor RBAC scopes, Excel uploader hierarchy logic, and profile correction queues.

---

## 3. Operations & Priorities Roadmap

* **Active Priorities:**
  1. Complete pre-deployment checklist for Phase 4.9 launch.
  2. Implement Phase 5: Payroll Integration algorithms (calculate unpaid hours based on check-in logs and unpaid leaves).
* **Known Risks:**
  - Database schema changes in future phases require daily Hostinger backups execution via cPanel.
  - Web deployments must verify directory symlinks cache triggers.

---

## 4. Documentation Architecture Directory

Refer directly to these primary standalone and consolidated documents inside the `/docs` directory:

1. **[HANDOVER.md](file:///c:/Users/Lenovo/AMS-V1/docs/HANDOVER.md):** The primary entry point for developers and AI continuity prompts.
2. **[TECHNICAL_MAP.md](file:///c:/Users/Lenovo/AMS-V1/docs/TECHNICAL_MAP.md):** Unified database schemas, codebase paths, route endpoints, and verification tests mapped by subsystem.
3. **[DEPLOYMENT_GUIDE.md](file:///c:/Users/Lenovo/AMS-V1/docs/DEPLOYMENT_GUIDE.md):** Setup scripts, cPanel deployment playbooks, backup tasks, SemVer rules, and rollback guides.
4. **[AMS_HISTORY.md](file:///c:/Users/Lenovo/AMS-V1/docs/AMS_HISTORY.md):** Narration of requirements evolution, project phase commits, and annotated release tags.
5. **[UI_OVERHAUL_SPEC.md](file:///c:/Users/Lenovo/AMS-V1/docs/UI_OVERHAUL_SPEC.md):** Consistency audits, component inventories, design debt items, and Phase 4.9 readiness parameters.
6. **[DECISION_LOG.md](file:///c:/Users/Lenovo/AMS-V1/docs/DECISION_LOG.md):** Immutable Architectural Decision Records (ADRs) logs database.
7. **[GIT_STANDARDS.md](file:///c:/Users/Lenovo/AMS-V1/docs/GIT_STANDARDS.md):** Conventional Commits formats, annotated tags criteria, and audit checklists.
8. **[PRODUCTION_READINESS.md](file:///c:/Users/Lenovo/AMS-V1/docs/PRODUCTION_READINESS.md):** Production readiness matrix, subsystem statuses (Completed/Planned/Future), environment specifications, and known limitations.
