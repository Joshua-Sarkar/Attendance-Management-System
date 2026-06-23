# AMS-V1 — Architectural Decision Log

This log documents the design reviews, alternatives evaluated, trade-offs accepted, and consequences of critical technical choices made throughout the life of the AMS-V1 project.

---

## ADR 1: Onboarding Credential Enforcement (Forced Password Reset)

### Problem
When employees are bulk-imported via Excel (Zimyo Engine) or created manually by an Administrator, they are assigned a default system-wide temporary password (`DEFAULT_EMPLOYEE_PASSWORD`). Leaving these temporary credentials active without renewal exposes the personnel profile and bank data to immediate security compromise.

### Context
Laravel Breeze provides complete authentication scaffolding but contains no built-in mechanism to intercept active sessions, block standard dashboard routes, or force password renewals. We need a centralized, maintainable control point that:
1. Intercepts all web requests.
2. Checks user status flags.
3. Restricts page interactions without bloating individual controllers.
4. Bypasses the blocks for the password change request inputs and the logout triggers to prevent infinite redirection loops.

### Alternatives Considered
* **Option A: Controller-level Checks:** Inject a helper check into every controller action.
  * *Trade-off:* High maintenance overhead; extremely prone to dev oversight during future feature integrations.
* **Option B: Route Grouping Scopes:** Split all active routes into two groups: "Verified Reset" and "Unverified Reset".
  * *Trade-off:* Complex route organization, complicates clean REST resources, and leads to messy route definitions.
* **Option C: HTTP Middleware Interceptor (Chosen):** Create a single custom middleware class registered in the application container's main web middleware pipeline.

### Chosen Solution
Implement the `CheckPasswordChange` route middleware, registered globally on the `web` pipeline in [app.php](file:///c:/Users/Lenovo/AMS-V1/bootstrap/app.php). The middleware queries the `must_change_password` boolean attribute on the authenticated user model. If true, and the current route is not `password.change`, `password.change.update`, or `logout`, it redirects the user to `/password/change`.

### Consequences
* **Positive:** Complete global coverage. Any new controllers or routes integrated in the future are automatically protected without extra code.
* **Negative:** If developers write external API routes or endpoints under the web group, they will get redirected unless explicitly whitelisted in the middleware route-name checks.
* **Related Files:**
  * [CheckPasswordChange.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Middleware/CheckPasswordChange.php) (middleware)
  * [PasswordController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/Auth/PasswordController.php) (routes handler)
  * [app.php](file:///c:/Users/Lenovo/AMS-V1/bootstrap/app.php) (middleware registrar)
* **Related Release:** Phase D (`v1.0-phase-d` completion commit `14a6f80`)

---

## ADR 2: Sequential Alphanumeric Employee ID Auto-Generation

### Problem
Exposing internal auto-increment database primary keys (`users.id`) directly in views, URLs, or export files exposes business metrics (employee volume) and creates insecure direct object reference (IDOR) vulnerabilities. We need a standardized corporate identifier that is unique, sequential, and formatted for corporate accounting.

### Context
Manual keying of employee codes leads to duplicates, formatting inconsistencies (e.g., mixing `EMP-1`, `emp_01`, and `EMP00001`), and data import mapping failures. The system must automatically suggest a formatted ID on user creation while validating uniqueness.

### Alternatives Considered
* **Option A: UUIDs:** Use random 36-character identifiers (e.g. `d3b07384d113...`).
  * *Trade-off:* High security, but impossible for HR and payroll staff to communicate verbally or print on ID badges.
* **Option B: Manual Input Only:** Force administrators to type unique codes.
  * *Trade-off:* High risk of duplicate key exceptions and typing fatigue.
* **Option C: Sequential suger prefix mapping (Chosen):** suggestion of alphanumeric codes starting at `EMP00001`, incrementing based on the highest existing code in the database.

### Chosen Solution
Create a helper method `generateEmployeeId()` inside [EmployeeController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/EmployeeController.php) that queries the maximum existing `employee_id` in the database, extracts the numeric suffix, increments it, and formats the output with a left zero pad of size 5, pre-pended with the `EMP` token.

### Consequences
* **Positive:** Consistent formatting (`EMP00001` to `EMP99999`), zero manual overhead for administrators, easy alignment with Zimyo imports.
* **Negative:** Relies on retrieving the highest ID which could create a race condition if two admins click create at the exact same millisecond. Since the database unique index on `employee_id` is active, it throws a query exception rather than saving duplicates, making it safe.
* **Related Files:**
  * [EmployeeController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/EmployeeController.php) (suggestion and creation handler)
* **Related Release:** Phase C.1 (`v1.0-phase-c.1` completion commit `e37dd81`)

---

*(Subsequent ADRs documented in respective phase commits)*
