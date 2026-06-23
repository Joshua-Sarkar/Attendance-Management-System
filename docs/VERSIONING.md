# AMS-V1 — Versioning and Deployment Strategy

This document defines the versioning guidelines, commit standards, release tag structures, rollback strategies, hotfix paths, and deployment workflows for all future AMS-V1 development. Adhering to these patterns ensures system reliability, database safety, and tracking.

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

## 3. Release Naming Guidelines

Every production release must have release notes in the repository changelog or GitHub releases page:
* **Release Title:** Match the version string and target module (e.g., `AMS-V1 v1.2.0: Leave Workflow & Security Hardening`).
* **Release Structure:**
  * **Summary:** A brief 2-3 sentence overview of the release goals.
  * **What's Changed:** Categorized bullet points (`### Features`, `### Bug Fixes`, `### Security`).
  * **Database Changes:** List any migration scripts that must be executed.
  * **Assets & Dependencies:** Mention any changes to npm or composer dependencies.

---

## 4. Hotfix Procedures

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

## 5. Rollback Procedures

If a deployment fails, use the following guidelines to restore service.

### 1. Code Rollback
To return the code to a previous release tag:
```bash
# Fetch latest repository state
git fetch --tags

# Force checkout the target tag
git checkout v1.2-phase-4.6

# Re-run build and dependency setup to match this release state
composer install --no-dev --optimize-autoloader
npm run build
```

### 2. Database Migration Rollback (Extremely Critical)
If a deployment fails due to a migration issue, rollback the last migration:
```bash
# Rollback the last migration step
php artisan migrate:rollback --step=1
```

> [!CAUTION]
> **Data Loss Prevention:** Do not run `migrate:reset` or `migrate:fresh` in production, as this will clear the database. If a migration dropped a column or altered data types, restore the database from the last backup instead of running automated rollback scripts.

### 3. Production Backups (Hostinger cPanel)
* **Automatic Backups:** Enforce daily automatic MySQL backups in cPanel.
* **Manual Snapshot:** Take a database snapshot via phpMyAdmin before running any migrations during deployment:
  * Log in to cPanel -> **phpMyAdmin**.
  * Select `ams_db`, click **Export**, and save the SQL file locally.
* **Recovery Steps:**
  * Import the backup SQL file via phpMyAdmin if a database migration error occurs.

---

## 6. Deployment Procedures

Use these steps for deployments to Hostinger Shared Linux Servers:

### Phase A: Pre-deployment Checklist
1. Verify that the local test suite is passing: `php artisan test` (must return 100% success).
2. Export a backup snapshot of the production database using cPanel phpMyAdmin.
3. Verify that the `.env` configuration file on the production server is correct (e.g. `APP_ENV=production`, `APP_DEBUG=false`, correct database credentials, `DEFAULT_EMPLOYEE_PASSWORD`).

### Phase B: Execution Steps
Run these commands from the terminal on the target hosting environment:

```bash
# 1. Pull code changes
git checkout main
git pull origin main

# 2. Update dependencies
composer install --no-dev --optimize-autoloader

# 3. Compile asset packages
npm install
npm run build

# 4. Run database migrations
php artisan migrate --force

# 5. Clear and optimize application caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Phase C: Post-deployment Validation
1. Access the application login screen and verify that the pages load correctly.
2. Log in using test credentials for each user role (`admin`, `manager`, and `employee`) to verify role-based layouts.
3. Verify that the live clock ticker on the dashboard shows the correct timezone: `Asia/Kolkata` (IST).
4. Run a test import sheet or submit a test leave request to verify transaction logic.
