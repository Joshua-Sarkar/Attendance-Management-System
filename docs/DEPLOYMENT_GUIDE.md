# AMS-V1 — Deployment & Versioning Operations Guide

This document defines the deployment workflows, server configurations, backup protocols, versioning structures, branching strategies, and emergency rollback procedures for the Attendance Management System Version 1 (AMS-V1).

---

## 1. Local Development Setup

Follow these steps to set up AMS-V1 in a local development environment:

### Prerequisites
* PHP 8.2+
* Composer
* Node.js & npm
* SQLite (default for local runs)

### Steps
1. **Clone the Repository:**
   ```bash
   git clone <repository-url> ams-v1
   cd ams-v1
   ```
2. **Install Dependencies:**
   ```bash
   composer install
   npm install
   ```
3. **Configure Environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Verify that `.env` is configured to use SQLite:*
   ```env
   DB_CONNECTION=sqlite
   # Leave DB_DATABASE empty for local auto-generation or point to database/database.sqlite
   ```
4. **Run Database Migrations & Seeds:**
   ```bash
   touch database/database.sqlite
   php artisan migrate --seed
   ```
5. **Compile Build Assets:**
   ```bash
   npm run build
   ```
6. **Start Local Servers:**
   ```bash
   # Terminal 1: Starts PHP server
   php artisan serve

   # Terminal 2 (optional, for development changes): Starts Vite hot-reload server
   npm run dev
   ```

---

## 2. Hostinger Production Deployment Workflow

Deploying to Hostinger Shared Linux Servers using cPanel access:

### Prerequisites
* Production domain/subdomain configured in cPanel.
* MySQL database and user created in MySQL Database Wizard.
* Target directory mapped (e.g. `public_html/ams`).

### Deployment Steps
1. **Prepare Code Base (Local):**
   Run the build steps locally to generate production-ready assets:
   ```bash
   npm run build
   ```
   Verify that the `public/build` directory is compiled.
2. **Push to GitHub:**
   Commit the build assets and code changes, then push to the `main` branch.
3. **SSH to Hostinger Server:**
   Connect to Hostinger via SSH:
   ```bash
   ssh <username>@<server-ip>
   cd <path-to-target-directory>
   ```
4. **Pull Latest Changes:**
   ```bash
   git fetch origin
   git checkout main
   git pull origin main
   ```
5. **Run Composer Installs (Optimized for Production):**
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
6. **Execute Production Migrations:**
   ```bash
   php artisan migrate --force
   ```
7. **Clear & Cache Configurations:**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

---

## 3. Production Migration Checklist

Before running migrations in production, verify the following:

- [ ] **Check Backup:** Export a snapshot of the current MySQL database via phpMyAdmin.
- [ ] **Validate Script:** Run the migrations in a staging/local database first to verify SQL statements.
- [ ] **Lock Out Requests (optional but recommended for major migrations):** Put the application in maintenance mode to prevent user operations during migration:
  ```bash
  php artisan down --secret="secret-recovery-token"
  ```
- [ ] **Execute Migration:** Run the migration command:
  ```bash
  php artisan migrate --force
  ```
- [ ] **Bring Application Online:** Disable maintenance mode:
  ```bash
  php artisan up
  ```

---

## 4. Cache Clearing Checklist

Run these commands if the application displays outdated views, layout elements, or configurations:

* **Clear All Caches (Standard Reset):**
  ```bash
  php artisan cache:clear
  php artisan config:clear
  php artisan route:clear
  php artisan view:clear
  ```
* **Optimize for Production (Regenerate Cache):**
  ```bash
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  ```

---

## 5. Database Backup Procedures

### A. Automatic Scheduled Backups (Hostinger cPanel)
* Log in to the Hostinger cPanel.
* Navigate to **Files -> Backups**.
* Select **Database Backups** and verify that daily backups are active.

### B. Manual Backup Snapshot (phpMyAdmin)
Perform a manual snapshot before making code changes or running database migrations:
1. Log in to cPanel and open **phpMyAdmin**.
2. Select the `ams_db` database in the left sidebar.
3. Click the **Export** tab.
4. Select the **Quick** export method and click **Go**.
5. Save the generated `.sql` file in a secure location.

---

## 6. Rollback Procedures

If a deployment fails, use the following guidelines to restore service.

### A. Code Rollback
To return the code to a previous release tag:
```bash
# Fetch latest repository state
git fetch --tags

# Force checkout the target tag
git checkout v1.2-phase-4.7.3

# Re-run build and dependency setup to match this release state
composer install --no-dev --optimize-autoloader
npm run build
```

### B. Database Migration Rollback (Extremely Critical)
If a deployment fails due to a migration issue, rollback the last migration:
```bash
# Rollback the last migration step
php artisan migrate:rollback --step=1
```

> [!CAUTION]
> **Data Loss Prevention:** Do not run `migrate:reset` or `migrate:fresh` in production, as this will clear the database. If a migration dropped a column or altered data types, restore the database from the last backup instead of running automated rollback scripts.

### C. phpMyAdmin Recovery Steps
* Import the backup SQL file via phpMyAdmin if a database migration error occurs.

---

## 7. Emergency Recovery Procedures

In the event of a critical server failure (e.g. filesystem corruption or database crashes):

### Step 1: Re-create Filesystem
If the local server directories are corrupted:
1. Re-clone the repository from GitHub.
2. Re-install all vendor packages via Composer and npm.
3. Re-link public storage:
   ```bash
   php artisan storage:link
   ```

### Step 2: Restore Database Snapshot
1. Log in to phpMyAdmin, select the database, check all tables, and click **Drop** (re-initializing to an empty schema).
2. Click the **Import** tab.
3. Choose the last backup SQL snapshot and click **Go**.
4. Access the application login screen and verify employee credentials.

---

## 8. Versioning Guidelines (SemVer)

AMS-V1 follows **Semantic Versioning 2.0.0** (SemVer) rules.

### Version Formats
* **`v[Major].[Minor].[Patch]`**: E.g. `v1.2.0`
  * **`Major`**: Breaking API changes, major redesigns, or structural database reorganizations.
  * **`Minor`**: New features, additional modules, or functional extensions (e.g. adding the import engine).
  * **`Patch`**: Bug fixes, minor layout adjustments, security updates, or database index changes.
* **`v[Major].[Minor]-phase-[PhaseNum]`**: E.g. `v1.2-phase-4.7.3`
  * Applied at the completion of a major phase to coordinate progress reports.

---

## 9. Branching Strategy & Git Branch Taxonomy

To maintain historical traceability, development follows a structured branching model:

1. **`main` (Active / Production):** The source of truth for all deployed features. Deployed directly to Hostinger production. All release tags point here.
2. **`develop` (Historical):** Retained as historical log for initial phase features integration.
3. **`master` (Historical):** Replaced by `main` as active production path.
4. **`hotfix/[module]-[short-desc]`:** Created from `main` to patch production bugs. Merged back to `main` with annotated release tags.
5. **Feature / Topic branches:** Specific task directories mapped to individual phase items (e.g. `ui-layout`).
