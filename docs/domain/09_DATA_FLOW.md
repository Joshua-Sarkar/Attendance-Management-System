# 09. Data Flow Diagrams

This document illustrates the execution pathways, transaction boundaries, and request lifecycles for major operations in the Attendance Management System.

---

## 1. Login Session & Onboarding Verification

When a user logs in, the request passes through the onboarding middleware to verify password status before loading dashboards.

```mermaid
sequenceDiagram
    actor User as Employee / Admin
    participant Route as routes/web.php
    participant MW as CheckPasswordChange Middleware
    participant Controller as AuthenticatedSessionController
    participant PasswordCtrl as PasswordController
    participant DB as SQLite Database

    User->>Controller: POST /login with credentials
    activate Controller
    Controller->>DB: Query user records by email
    DB-->>Controller: Return hashed password & must_change_password flag
    Controller->>Controller: Validate credentials
    Controller-->>User: Authenticated Session & Redirect to /dashboard
    deactivate Controller

    User->>Route: Navigate to /dashboard
    activate Route
    Route->>MW: Handle request
    activate MW
    alt must_change_password is true
        MW-->>User: Redirect to /password/change (302)
        User->>PasswordCtrl: GET /password/change (Render form)
        User->>PasswordCtrl: POST /password/change with new password
        PasswordCtrl->>DB: Update password & must_change_password = false
        PasswordCtrl-->>User: Redirect to /dashboard (Onboarded)
    else must_change_password is false
        MW->>Route: Allow request to proceed
        Route-->>User: Render /dashboard View
    end
    deactivate MW
    deactivate Route
```

---

## 2. Attendance Check-in Workflow

Logs employee daily attendance, checks grace thresholds, and sets late arrival half-day classifications.

```mermaid
sequenceDiagram
    actor Employee
    participant Route as routes/web.php
    participant Controller as AttendanceController
    participant Service as AttendanceService
    participant DB as SQLite Database

    Employee->>Route: POST /attendance/check-in
    activate Route
    Route->>Controller: checkIn(Request)
    activate Controller
    Controller->>Service: checkIn(User)
    activate Service

    Service->>DB: Query user's department timings
    DB-->>Service: Return shift_start_time & grace_minutes (e.g. 09:30, 5 mins)
    Service->>Service: Calculate grace threshold (09:35)
    
    alt Clock-in <= threshold (09:35)
        Service->>DB: Save Attendance (status = present, classification = full_day)
    else Clock-in > threshold (09:35)
        Service->>DB: Save Attendance (status = late, classification = half_day, reason = late_arrival)
    end
    
    DB-->>Service: Return created/updated record
    Service-->>Controller: Return Attendance model
    deactivate Service
    Controller-->>Employee: Redirect Back (with success toast)
    deactivate Controller
    deactivate Route
```

---

## 3. Administrative Attendance Override

Allows Administrators to manually override status/classifications, enforcing a minimum 5-character reason and preserving the original values as an audit trail.

```mermaid
sequenceDiagram
    actor Admin
    participant Route as routes/web.php
    participant MW as EnsureUserIsAdmin Middleware
    participant Controller as AttendanceOverrideController
    participant DB as SQLite Database

    Admin->>Route: POST /admin/attendance/overrides
    activate Route
    Route->>MW: Check role
    activate MW
    MW->>Route: Authorized (Admin)
    deactivate MW
    Route->>Controller: store(Request)
    activate Controller
    Controller->>Controller: Validate inputs (Reason min 5 chars, date, status, user_id)
    
    Controller->>DB: Query existing Attendance for user & date
    alt Record does not exist
        Controller->>DB: Query active leaves/Sundays for date
        DB-->>Controller: Return leave state or weekly_off default
        Controller->>Controller: Set automatic_status = default (e.g., absent)
    else Record exists
        Controller->>Controller: Preserve existing status/classification as automatic_*
    end
    
    Controller->>DB: Update/Create Attendance (status = override, is_overridden = true, overridden_by = admin_id, override_reason)
    DB-->>Controller: Commit transaction
    Controller-->>Admin: Redirect Back (success toast)
    deactivate Controller
    deactivate Route
```

---

## 4. Leave Request Approval & Balance Ledger

Illustrates the transactional balance update process. It uses database transactions and pessimistic row locks to prevent double-deduction conflicts.

```mermaid
sequenceDiagram
    actor Manager
    participant Route as routes/web.php
    participant Controller as LeaveRequestController
    participant DB as SQLite Database
    participant Ledger as leave_ledger_entries

    Manager->>Route: POST /leaves/{leaveRequest}/approve
    activate Route
    Route->>Controller: approve(Request, LeaveRequest)
    activate Controller
    
    Controller->>DB: Begin Database Transaction
    Controller->>DB: Fetch User lockForUpdate()
    note over DB: Lock applicant row, blocking concurrent requests
    
    Controller->>DB: Verify user.leave_balance >= leaveRequest.total_days
    
    alt Balance is sufficient
        Controller->>DB: Deduct total_days from user.leave_balance
        Controller->>Ledger: Insert deduction entry (negative amount)
        Controller->>DB: Update LeaveRequest status = approved
        Controller->>DB: Commit Transaction
        note over DB: Release locks
        Controller-->>Manager: Redirect Back (success toast)
    else Balance is insufficient
        Controller->>DB: Rollback Transaction
        note over DB: Release locks
        Controller-->>Manager: Redirect Back (insufficient balance error)
    end
    deactivate Controller
    deactivate Route
```

---

## 5. Birthday Leave Credit & Submission

The system automatically verifies eligibility, locks birthday leave credits, and approves the request in a single transaction.

```mermaid
sequenceDiagram
    actor Employee
    participant Route as routes/web.php
    participant Controller as LeaveRequestController
    participant Service as LeaveBalanceService
    participant DB as SQLite Database
    
    Employee->>Route: POST /leaves (Submit Birthday Leave)
    activate Route
    Route->>Controller: store(Request)
    activate Controller
    Controller->>Controller: Verify leave_type = complimentary, total_days = 1
    Controller->>Service: submitBirthdayLeave(User, date)
    activate Service
    
    Service->>DB: Begin Database Transaction
    Service->>DB: Fetch user profile and sync/find active birthday credit token
    DB-->>Service: Return active LeaveCredit (source_identifier = birthday_226)
    
    Service->>DB: Lock User & lock Credit for update
    Service->>DB: Set credit.used_amount = 1.00
    Service->>DB: Create LeaveRequest (status = approved, leave_type = complimentary)
    Service->>DB: Log LeaveRequestLog entries (applied -> approved)
    Service->>DB: Commit Transaction
    
    Service-->>Controller: Return approved LeaveRequest
    deactivate Service
    Controller-->>Employee: Redirect to /leaves (auto-approved success)
    deactivate Controller
    deactivate Route
```

---

## 6. Workforce Excel Uploader (Zimyo Engine)

Bulk parses new users and departments in Pass 1, and maps reporting hierarchies in Pass 2.

```mermaid
sequenceDiagram
    actor Admin
    participant Controller as ImportController
    participant Service as EmployeeImportService
    participant DB as SQLite Database

    Admin->>Controller: Upload Zimyo Excel Sheet
    activate Controller
    Controller->>Service: import(filePath)
    activate Service
    Service->>DB: Begin Transaction
    
    note over Service: PASS 1: Initialize Departments, Users & Profiles
    loop Every Row in Sheet
        Service->>DB: Find/Create Department
        Service->>DB: Create/Update User (Hashed password, must_change = true)
        Service->>DB: Create/Update EmployeeProfile (AES-256 encrypted casts)
        Service->>DB: Initialize standard leave balance (2.00 days)
        Service->>Service: Cache standardized ID -> user.id map in-memory
    end
    
    note over Service: PASS 2: Resolve Reporting Managers Hierarchy
    loop Every Row in Sheet
        Service->>Service: Resolve manager ID using cached in-memory map
        Service->>DB: Update user.manager_id to manager's user.id
        opt Manager role is 'employee'
            Service->>DB: Promote Manager role to 'manager'
        end
    end
    
    Service->>DB: Commit Transaction
    Service-->>Controller: Return stats (created, updated, error logs array)
    deactivate Service
    Controller-->>Admin: Render summary views (errors details if any)
    deactivate Controller
```

---

## 7. Dashboard Loading & Stats Processing

```mermaid
sequenceDiagram
    actor Manager
    participant Controller as DashboardController
    participant Service as AttendanceService
    participant DB as SQLite Database

    Manager->>Controller: GET /dashboard (with filters)
    activate Controller
    Controller->>Service: getTodayStats(date, departmentId, manager)
    activate Service
    
    Service->>DB: Query active employees for manager/department
    DB-->>Service: Return User collection
    
    Service->>DB: Query Attendance records for date & users
    DB-->>Service: Return Attendance records
    
    Service->>DB: Query approved LeaveRequests overlapping date & users
    DB-->>Service: Return LeaveRequests
    
    loop Every Employee
        Service->>Service: Match Attendance and approved Leave Requests
        opt No check-in &approved Leave Request exists
            Service->>Service: Resolve status as 'on_leave' or 'wfh'
        opt No check-in & is Sunday
            Service->>Service: Resolve status as 'weekly_off'
        opt No check-in & no leave/Sunday
            Service->>Service: Resolve status as 'absent'
        end
        Service->>Service: Collate statistics (present, late, absent, averages)
    end
    
    Service-->>Controller: Return stats array
    deactivate Service
    Controller-->>Manager: Render /dashboard page
    deactivate Controller
```
