# AMS-V1 — System Architecture Map

This document describes the high-level subsystem relationships, data flow boundaries, and operational dependencies of the Attendance Management System Version 1 (AMS-V1).

---

## 1. Subsystem Interaction Model

The diagram below shows how the 9 major subsystems interact with each other and route their respective data dependencies:

```mermaid
graph TD
    %% Subsystems
    Auth["1. Authentication & Security<br>(Breeze / Middleware / Encryption)"]
    Workforce["2. Workforce Management<br>(Departments / Users CRUD)"]
    Profiles["3. Employee Profiles<br>(Extended Metadata / Encryption casts)"]
    Attendance["4. Attendance Tracking<br>(Clock-in / Delay math / Audit)"]
    Leaves["5. Leave Request Management<br>(Submissions / Approval Flows)"]
    Ledger["6. Leave Balance Ledger<br>(Double-Entry ledger / Pessimistic locks)"]
    Imports["7. Zimyo Excel Import Engine<br>(Bulk parser / Onboarding pipeline)"]
    Corrections["8. Profile Correction Requests<br>(Employee queue / Sidebar count)"]
    Ops["9. Deployment & Infrastructure<br>(Hostinger / SQLite local / Git tags)"]

    %% Relationships
    Auth -->|Intercepts Sessions / Checks Onboarding| Workforce
    Auth -->|Enforces Role Access Limits| Attendance
    Auth -->|Enforces Role Access Limits| Leaves
    Auth -->|Enforces Role Access Limits| Corrections
    
    Workforce -->|Owns User Primary Records| Profiles
    Workforce -->|Provides Assigned Employee Roasters| Attendance
    Workforce -->|Determines Leave Approval Chain| Leaves
    
    Profiles -->|Rest-Layer Data Encrypt| Auth
    
    Imports -->|Creates Departments / Users| Workforce
    Imports -->|Creates Extended Profiles| Profiles
    Imports -->|Initializes Ledger Balances| Ledger
    
    Attendance -->|Physical Clock-in Overrides Leave| Leaves
    
    Leaves -->|Triggers Balance Updates| Ledger
    
    Corrections -->|Sends Requests to Admin| Workforce
    
    Ops -->|Deploys and Recovers Tables| Workforce
```

---

## 2. Subsystem Relationships & Data Flows

### Authentication & Security Relationships
* **Authentication → Workforce Management & Dashboards:** 
  * The `CheckPasswordChange` middleware intercepts all incoming requests to workforce and dashboard routes.
  * If the authenticated user has `must_change_password = true`, they are blocked and redirected to the password change view.
* **Authentication → Role-Based Access Control (RBAC):**
  * Controllers map user roles (`admin`, `manager`, `employee`) to restrict query boundaries.
  * Route middleware (`EnsureUserIsAdmin`) restricts import routes, correction queues, and audit dashboards to admin staff.

---

*(Subsystem relationships for other domains will be detailed in respective phase commits)*
