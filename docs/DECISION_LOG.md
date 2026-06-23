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

*(Subsequent ADRs documented in respective phase commits)*
