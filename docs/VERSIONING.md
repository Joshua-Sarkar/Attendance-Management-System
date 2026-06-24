# AMS-V1 — Versioning and Deployment Strategy

This document defines the versioning guidelines, branching taxonomy, hotfix paths, rollback strategies, and deployment workflows for AMS-V1.

> [!IMPORTANT]
> For the authoritative repository governance rules, detailed commit requirements, release tagging templates, documentation synchronization workflows, and audit checklists, refer directly to **[GIT_STANDARDS.md](file:///c:/Users/Lenovo/AMS-V1/docs/GIT_STANDARDS.md)**. All commits and releases must follow the standards defined there.

---

## 1. Commit Naming Conventions

AMS-V1 follows a structured format based on Conventional Commits. Every commit message must specify a type, scope, and a descriptive, present-tense message.

### Format
`type(scope): present-tense description`

### Allowed Types
* **`feat`**: A new user-facing feature (e.g. `feat(leave): add unpaid leave approval`).
* **`fix`**: A bug fix affecting runtime behavior or database storage (e.g. `fix(profile): support string experience values`).
* **`refactor`**: Code changes that neither fix a bug nor add a feature, but improve architecture (e.g. `refactor(attendance): move delay math to AttendanceService`).
* **`style`**: Layout styling changes, layout adjustments, and aesthetic styling updates (e.g. `style(dashboard): adjust card hover tilt angles`).
* **`test`**: Adding, modifying, or repairing test suites (e.g. `test(ledger): add concurrent approval tests`).
* **`docs`**: Updates to documentation files, markdown guides, or comments (e.g. `docs(readme): update deployment directions`).
* **`chore`**: Maintenance tasks, build asset updates, dependency updates, and boilerplate configuration modifications (e.g. `chore(deps): update composer packages`).

### Examples
* `feat(audit): add department filters to attendance ledger`
* `fix(auth): prevent redirection loops on password reset`
* `test(security): add encryption validation for bank accounts`
* `style(sidebar): implement badge scaling and animations`

---

## 2. Versioning & Tag Naming Conventions

AMS-V1 follows **Semantic Versioning 2.0.0** (SemVer) with custom phase tags to match project goals.

### Version Formats
* **`v[Major].[Minor].[Patch]`**: E.g. `v1.2.0`
  * **`Major`**: Breaking API changes, major redesigns, or structural database reorganizations.
  * **`Minor`**: New features, additional modules, or functional extensions (e.g. adding the import engine).
  * **`Patch`**: Bug fixes, minor layout adjustments, security updates, or database index changes.
* **`v[Major].[Minor]-phase-[PhaseNum]`**: E.g. `v1.2-phase-4.6`
  * Applied at the completion of a major phase to coordinate progress reports.

### Git Tag Command Standards
Always use annotated git tags (`-a`) instead of lightweight tags. Include a detailed message:
`git tag -a v1.2.1 -m "Short description of the release highlights"`

---

## 3. Git Branch Strategy & Taxonomy

AMS-V1 uses a structured branch taxonomy to keep development history traceable:
* **`main` (Active / Production):** The source of truth for all deployed features. Deployed directly to Hostinger production. All release and docs tags point here.
* **`develop` (Legacy Development):** Used during early phase C/D/E integrations. Now kept as a historical record.
* **`master` (Legacy Production):** Legacy production branch replaced by `main`.
* **`phase-d-leave-management` (Topic / Phase Branch):** Historical branch used to write early leave request models.
* **`ui-layout` / `ui-redesign` (Topic / Feature Branches):** Specialized branches used to test dashboard stylesheets and glassmorphic designs.
* **`hotfix/[module]-[short-desc]` (Operational / Hotfix):** Created directly from `main` to patch production bugs. Merged back to `main` with annotated tag updates.

---

## 4. Release Naming Guidelines

Every production release must have release notes in the repository changelog or GitHub releases page:
* **Release Title:** Match the version string and target module (e.g., `AMS-V1 v1.2.0: Leave Workflow & Security Hardening`).
* **Release Structure:**
  * **Summary:** A brief 2-3 sentence overview of the release goals.
  * **What's Changed:** Categorized bullet points (`### Features`, `### Bug Fixes`, `### Security`).
  * **Database Changes:** List any migration scripts that must be executed.
  * **Assets & Dependencies:** Mention any changes to npm or composer dependencies.

---

## 5. Hotfix Procedures

Hotfixes address issues in production that cannot wait for a scheduled release cycle.

```
[Issue Identified in Production]
               │
               ▼
[Branch from main: hotfix/issue-description]
               │
               ▼
[Develop Fix & Write Targeted Feature Test]
               │
               ▼
[Verify Test Suite Passes (98/98 tests green)]
               │
               ▼
[Merge to main & Tag Release (vX.Y.Z-hotfix)]
               │
               ▼
[Deploy to Hostinger & Clear Caches]
```

### Protocol Details
1. **Branch Creation:** Create a branch from `main` using the naming structure: `hotfix/[module]-[short-description]` (e.g. `hotfix/profile-experience-crash`).
2. **Local Verification:**
   * Write a regression test to replicate the bug.
   * Write the code correction.
   * Run the test suite: `php artisan test` (confirming all tests pass).
3. **Merging & Tagging:**
   * Merge the hotfix back to `main`.
   * Create an annotated patch tag: `git tag -a v1.2.1 -m "Hotfix: resolve string experience parsing issue during import"`.
4. **Fast-track Deploy:** Push `main` and tags to the repository, pull on the production server, run migrations, and rebuild assets.

---

## 6. Rollback Procedures
For detailed step-by-step instructions on rolling back the codebase, restoring MySQL backup snapshots, and reversing migrations safely on the Hostinger production server, refer to [DEPLOYMENT.md: Section 6 (Rollback Procedures)](file:///c:/Users/Lenovo/AMS-V1/docs/DEPLOYMENT.md#6-rollback-procedures).

---

## 7. Deployment Procedures
For the pre-deployment checklist, server execution steps, and post-deployment validation protocols, refer directly to [DEPLOYMENT.md: Section 2 (Hostinger Deployment Workflow)](file:///c:/Users/Lenovo/AMS-V1/docs/DEPLOYMENT.md#2-hostinger-deployment-workflow) and [Section 3 (Production Migration Checklist)](file:///c:/Users/Lenovo/AMS-V1/docs/DEPLOYMENT.md#3-production-migration-checklist).

