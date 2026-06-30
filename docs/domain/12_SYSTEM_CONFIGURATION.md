# 12. System Configuration Map

This document serves as the directory for configurable operational parameters in the system, detailing default values, storage scopes, and files referencing each setting.

---

## 1. Timing & Grace Configuration

### A. Default Shift Start Time
- **Current Value**: `'09:30'`
- **Default Value**: `'09:30'`
- **Source**: Config file (`config/attendance.php` via env `ATTENDANCE_START_TIME`) and model defaults.
- **Responsible Module**: Attendance Tracking.
- **Files Using Configuration**:
  - [config/attendance.php](file:///c:/Users/Lenovo/AMS-V1/config/attendance.php#L4)
  - [app/Models/Attendance.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/Attendance.php#L61)
  - [app/Http/Controllers/AttendanceController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/AttendanceController.php#L124)

### B. Default Grace Minutes
- **Current Value**: `15`
- **Default Value**: `15`
- **Source**: Config file (`config/attendance.php` via env `ATTENDANCE_GRACE_MINUTES`).
- **Responsible Module**: Attendance Tracking.
- **Files Using Configuration**:
  - [config/attendance.php](file:///c:/Users/Lenovo/AMS-V1/config/attendance.php#L5)
  - [app/Models/Attendance.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/Attendance.php#L62)
  - [app/Http/Controllers/AttendanceController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/AttendanceController.php#L125)

### C. Department Specific Shift (e.g. Healthcare Department)
- **Current Value**: `shift_start_time = '10:00:00'`, `grace_minutes = 5`
- **Default Value**: `shift_start_time = '09:30:00'`, `grace_minutes = 5`
- **Source**: Database (`departments` table columns).
- **Responsible Module**: Department Management.
- **Files Using Configuration**:
  - [app/Models/Attendance.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/Attendance.php#L48-L52)
  - [app/Services/AttendanceService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/AttendanceService.php#L21-L25)

### D. New Rules Transition Date
- **Current Value**: Derived from env `ATTENDANCE_NEW_RULES_START_DATE`
- **Default Value**: `null` (historical fallback to `09:00` start / 15 grace)
- **Source**: Config file (`config/attendance.php` via env `ATTENDANCE_NEW_RULES_START_DATE`).
- **Responsible Module**: Attendance Tracking.
- **Files Using Configuration**:
  - [config/attendance.php](file:///c:/Users/Lenovo/AMS-V1/config/attendance.php#L8)
  - [app/Models/Attendance.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/Attendance.php#L53)

---

## 2. Leave & Ledger Configuration

### A. Monthly Accrual Credit
- **Current Value**: `2.00` days
- **Default Value**: `2`
- **Source**: Config file (`config/attendance.php` via env `LEAVE_MONTHLY_ACCRUAL_RATE`).
- **Responsible Module**: Leave Request Management & Ledger.
- **Files Using Configuration**:
  - [config/attendance.php](file:///c:/Users/Lenovo/AMS-V1/config/attendance.php#L7)
  - [app/Console/Commands/AccrueLeavesCommand.php](file:///c:/Users/Lenovo/AMS-V1/app/Console/Commands/AccrueLeavesCommand.php#L43)

### B. Opening Balance Credit
- **Current Value**: `2.00` days
- **Default Value**: `2.00`
- **Source**: Code (Hardcoded).
- **Responsible Module**: Leave Request Management & Ledger.
- **Files Using Configuration**:
  - [app/Services/LeaveBalanceService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/LeaveBalanceService.php#L92)

### C. Birthday Leave Allocation Parameters
- **Unlock Window**: `1` day before birthday (Hardcoded).
- **Expiry Duration**: `1` year from unlock date (Hardcoded).
- **Credit Amount**: `1.00` day (Hardcoded).
- **Source**: Code (Hardcoded).
- **Responsible Module**: Birthday Leave.
- **Files Using Configuration**:
  - [app/Models/User.php](file:///c:/Users/Lenovo/AMS-V1/app/Models/User.php#L121-L155)

---

## 3. Security & Account Defaults

### A. Default Provisioning Password
- **Current Value**: Derived from env `DEFAULT_EMPLOYEE_PASSWORD`
- **Default Value**: `null` (Must be configured in env to pass startup assertions)
- **Source**: Config file (`config/employees.php` via env `DEFAULT_EMPLOYEE_PASSWORD`).
- **Responsible Module**: Authentication & Security.
- **Files Using Configuration**:
  - [config/employees.php](file:///c:/Users/Lenovo/AMS-V1/config/employees.php#L5)
  - [app/Services/EmployeeImportService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/EmployeeImportService.php#L24)
  - [tests/Feature/PasswordStrategySecurityTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/PasswordStrategySecurityTest.php#L29)

---

## 4. System Operational Thresholds

### A. Half-Day Working Hour Limit
- **Current Value**: `4.0` hours
- **Default Value**: `4.0`
- **Source**: Code (Hardcoded).
- **Responsible Module**: Attendance Tracking.
- **Files Using Configuration**:
  - [app/Services/AttendanceService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/AttendanceService.php#L89)

### B. Weekly Off Exclusions
- **Current Value**: `'Sunday'`
- **Default Value**: `'Sunday'`
- **Source**: Code (Hardcoded).
- **Responsible Module**: Attendance Tracking.
- **Files Using Configuration**:
  - [app/Services/AttendanceService.php](file:///c:/Users/Lenovo/AMS-V1/app/Services/AttendanceService.php#L136)
  - [app/Http/Controllers/AttendanceController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/AttendanceController.php#L70)
  - [tests/Feature/WorkingDaysTest.php](file:///c:/Users/Lenovo/AMS-V1/tests/Feature/WorkingDaysTest.php#L49)

### C. Form Min Character Limits (e.g. Override reason)
- **Current Value**: `5` characters
- **Default Value**: `5`
- **Source**: Code (Validation rules).
- **Responsible Module**: Attendance Overrides, Leave Request Management.
- **Files Using Configuration**:
  - [app/Http/Controllers/AttendanceOverrideController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/AttendanceOverrideController.php#L21)
  - [app/Http/Controllers/LeaveRequestController.php](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/LeaveRequestController.php#L110)

---

## 5. Related Modules & Cross References
- **[02_ATTENDANCE_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/02_ATTENDANCE_RULES.md)**: Reads shift start parameters for late arrivals.
- **[03_LEAVE_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/03_LEAVE_RULES.md)**: Reads monthly accrual credits parameters.
