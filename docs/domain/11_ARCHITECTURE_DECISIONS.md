# 11. Architecture Decisions & Design Principles

This document records the architectural decisions, structural patterns, and design rationales for the Attendance Management System. Future contributors must adhere to these design principles to ensure codebase cohesion.

---

## 1. Core Architectural Patterns

### Service-Oriented Architecture (SOA)
- **Decision**: All complex business calculations, status resolutions, and transactional database modifications must reside in dedicated Service classes (`app/Services/`) rather than inside Controllers or Eloquent Models.
- **Rationale**:
  - **Single Responsibility (SRP)**: Controllers should only validate incoming request data, enforce authorization checks, invoke the service layers, and route HTTP responses.
  - **Reusability**: Business calculations (like daily status calculations or leaf deductions) are needed across web controllers, Artisan console commands (e.g. `leaves:accrue`), and future API endpoints. Extracting logic to Services makes it accessible to all consumers.
  - **Testing**: Services can be unit-tested in isolation without mocking HTTP request and session context containers.

### Model Encapsulation
- **Decision**: Eloquent Models (`app/Models/`) should encapsulate database column casts, entity relationships, and lightweight accessors. They must never contain complex query orchestrations or mutator writes that touch other models.
- **Rationale**: Keeps model classes clean, predictable, and simple. Intersubsystem dependencies must be managed in the service layer.

---

## 2. Rationales for Subsystem Decisions

### A. Why AttendanceService Owns Attendance Calculations
- **Context**: Resolving a day's attendance requires checking check-in logs, querying approved leave requests, evaluating weekend status, and checking for admin overrides.
- **Rationale**:
  - Centralizing this logic inside `AttendanceService` prevents duplicate calculations. The employee dashboard, manager roster, and HR audit center all query `AttendanceService` to resolve status details, ensuring consistency across screens.

### B. Why Birthday Leave is Auto-Approved
- **Context**: Birthday leaves are auto-approved on submission, bypassing supervisor review.
- **Rationale**:
  - Birthday Leave is a pre-allocated complimentary benefit. The system already performs tenure eligibility checks and verifies the active token in the double-entry database ledger *before* allowing the submission.
  - Since the validity is restricted and balance checking is done programmatically, requiring human manager review is redundant and introduces administrative friction.

### C. Why Attendance Overrides are Stored on the Attendance Model
- **Context**: When an administrator overrides daily attendance, the override metadata (reason, type, overridden_at, and pre-override automatic calculated values) are written directly to columns on the `attendances` table record instead of a separate audit log table.
- **Rationale**:
  - **Locality of Reference**: By storing the override flag (`is_overridden`), admin ID, explanation, and automatic fallbacks on the attendance record itself, the database can fetch the full state of a day in a single index query. This avoids complex SQL joins against separate logs tables on high-traffic index pages.

### D. Why Department Shifts are Database-Driven
- **Context**: Timings (`shift_start_time`, `shift_end_time`) and `grace_minutes` are stored as columns on the `departments` table in the database instead of static env parameters.
- **Rationale**:
  - Stores operational settings where they can be configured by non-technical HR administrators via user interface settings panels. Storing them in code or `.env` files would require developer deployments to adjust standard shift timings or grace times.

---

## 3. Principles for Future Contributors

1. **Keep Controllers Thin**: Controllers must never contain database queries or math algorithms. If a controller method exceeds 20 lines of database execution, extract it to a Service.
2. **Isolate Configuration from Logic**: Hardcoded magic numbers or timing strings are strictly forbidden. Timings, balance rates, and durations must be retrieved from configuration files (`config/`) or environment parameters (`.env`).
3. **Respect Relational Boundaries**: Do not perform manual queries that bypass Eloquent relationships. Use `$user->directReports` or `$user->department` mappings to keep relations traceable.
4. **Leverage Service Reusability**: Before writing a new helper method or service, verify if the calculation is already handled by `AttendanceService` or `LeaveBalanceService`. Integrate with existing services rather than creating duplicate execution paths.
