# AMS-V1 — Test Coverage Map

This document indexes all verification suites, automated test files, and assertions protecting the subsystems of AMS-V1 from regression.

---

## 1. Authentication & Security Testing

### Automated Test Files
* **[PasswordStrategySecurityTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/PasswordStrategySecurityTest.php)**
  * *Coverage Focus:* Validates onboarding security flows, default temporary password resets, and forced change redirections.
  * *Scenarios Verified:*
    1. Verifies that user creation fails if the `DEFAULT_EMPLOYEE_PASSWORD` env variable is missing.
    2. Verifies that an Admin can reset any employee's password back to default.
    3. Verifies that resetting a password re-arms `must_change_password` and forces the employee to complete the redirection flow on subsequent requests.
* **[AuthenticationTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/Auth/AuthenticationTest.php)**
  * *Coverage Focus:* Standard login controls.
  * *Scenarios Verified:*
    1. Users can authenticate using valid email and password credentials.
    2. Users cannot authenticate with invalid passwords.
* **[PasswordConfirmationTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/Auth/PasswordConfirmationTest.php)**
  * *Coverage Focus:* Confirms session passwords before accessing sensitive actions.
  * *Scenarios Verified:*
    1. Password confirmation screen renders.
    2. Active sessions are successfully confirmed.
* **[PasswordResetTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/Auth/PasswordResetTest.php)**
  * *Coverage Focus:* Forgot-password link creation and reset token redemption.
  * *Scenarios Verified:*
    1. Reset password link email can be requested.
    2. Users can set a new password using a valid email verification token.
* **[PasswordUpdateTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/Auth/PasswordUpdateTest.php)**
  * *Coverage Focus:* Standard profile password change form validation.
  * *Scenarios Verified:*
    1. Correct password must be supplied to update credentials.
    2. New password must match validation requirements.
* **[ExampleTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/ExampleTest.php)**
  * *Coverage Focus:* Root context redirects.
  * *Scenarios Verified:*
    1. Unauthenticated guest hits `/` and redirects to `/login`.
    2. Authenticated user hits `/` and redirects to `/dashboard`.

---

*(Other domain tests detailed in respective phase commits)*
