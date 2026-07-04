# 03. Leave & Ledger Rules

This document details the business rules and ledger controls for planned, unplanned, and complimentary leave types, monthly accruals, and special birthday leave tokens.

---

## 1. Leave Categories & Eligibility Rules

### Intended Business Rule
The system supports four categories of leave:
1. **Planned Leave**: Scheduled holidays applied for in advance. Requires manager approval. Deducted from leave balance if approved. Explicitly marked as Paid.
2. **Unplanned Leave**: Emergency time-off. Applied for retroactively or on short notice. Requires manager approval. Bypasses regular leave balance validation and deductions, logging a zero-value ledger entry trail. Explicitly marked as Unpaid.
3. **Unpaid Leave**: Non-salaried time-off. Requires manager approval. Bypasses regular leave balance validation and deductions, but logs a zero-value ledger entry trail. Used by Payroll to calculate salary deductions.
4. **Birthday Leave (Paid)**: A special 1-day holiday allocated annually around the employee's birthday. It is a specialization of Paid Leave: it does not deduct from the standard leave balance (uses complimentary credit), is auto-approved if active, records a transparent `0.00` ledger entry, and is tagged with metadata `is_birthday = true`.

### Current Implementation
- Handled in [LeaveRequestController@store](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/LeaveRequestController.php).
- Overlap checking is performed on submission to prevent multiple active requests on the same calendar dates.
- Standard employees must specify a reason (minimum 5 characters) and select one of the three categories.

---

## 2. Double-Entry Leave Ledger Rules

### Intended Business Rule
- **Transactional Balances**: The user's `leave_balance` must never be modified directly. Every adjustment must be recorded as a row in the `leave_ledger_entries` audit trail. The `leave_balance` in the `users` table is a cached summary of the ledger sum.
- **Deduction and Refund**:
  - When a manager approves a Planned leave, the system deducts the `total_days` from `users.leave_balance` and records a ledger row of type `'deduction'` with a negative amount.
  - If a user cancels an approved paid leave, the balance must be restored, and a ledger row of type `'refund'` with a positive amount is created.
  - Administrative adjustments must be logged under the `'adjustment'` type.
- **Manual Overrides Sync**: Administrative overrides to daily attendance (e.g. overriding an absent day to `'paid_leave'`, or changing a `'paid_leave'` day back to `'present'`) must automatically create matching positive/negative `'adjustment'` ledger rows and update the user's `leave_balance` to keep status logs and leave balances fully reconciled.
- **Negative Balance Constraint**: If the configuration `'attendance.allow_negative_leave_balance'` is set to `false`, the reconciliation engine must validate remaining balances and reject any manual overrides or approvals that would cause the employee's `leave_balance` to drop below `0.00`.
- **Concurrency Protections**: The system must serialize balance updates using pessimistic database locks (`lockForUpdate()`) to prevent concurrent approvals or cancels from resulting in duplicate deductions (double-spend balance anomalies).

### Current Implementation
- All balance deductions, approvals, and manual overrides are run inside database transactions wrapped in `DB::transaction()` with a blocking row lock:
  `User::where('id', $userId)->lockForUpdate()->firstOrFail();`
- Overrides synchronization is implemented in [AttendanceService@applyBulkOverride](file:///c:/Users/Lenovo/AMS-V1/app/Services/AttendanceService.php):
  - Determines if the day was already deducted (via a previous override or approved leave request) and computes the net change.
  - Checks if negative balance is prevented (`config('attendance.allow_negative_leave_balance') === false`) and aborts with a validation error if the user balance would be exceeded.
  - Updates the balance and writes a transaction record to `leave_ledger_entries`.

---

## 3. Leave Accruals & Idempotency Rules

### Intended Business Rule
- **Initial Grant**: New employees receive an opening balance of 2.00 days upon creation.
- **Monthly Accrual**: On the 1st of every month, all active employees are credited with 2.00 additional leave days.
- **Idempotency Guard**: The monthly accrual job must be idempotent. If run multiple times in the same calendar month, it must skip users who have already received their accrual for that month.

### Current Implementation
- **Initializer**: [LeaveBalanceService::initializeUser](file:///c:/Users/Lenovo/AMS-V1/app/Services/LeaveBalanceService.php) sets the initial 2.00 balance and writes an `'opening_balance'` ledger entry.
- **Accrual Command**: Run via [AccrueLeavesCommand](file:///c:/Users/Lenovo/AMS-V1/app/Console/Commands/AccrueLeavesCommand.php) (`leaves:accrue`). It checks for `accrual` ledger entries in the current month for each user. If none exist, it credits 2.00 days and logs the accrual entry.

---

## 4. Birthday Leave & Token Rules

### Intended Business Rule
- **Complimentary Credit**: Eligible employees receive a 1.00-day birthday leave token.
- **Unlock Window**: The token is unlocked and synced `N` days before the employee's birthday (derived from configuration `'attendance.birthday_leave_unlock_days'`).
- **Validity & Expiry**: The token is valid for `M` years from its unlock date (derived from configuration `'attendance.birthday_leave_expiry_years'`). If unused, it is marked as `expired` and cannot be claimed.
- **Leap Year Rule**: If an employee is born on February 29 (leap year), in non-leap years their birthday is resolved to February 27. The token unlocks based on the configured window (e.g. Feb 26) and expires based on the configured duration.
- **Eligibility (Tenure)**: Employees cannot claim birthday leaves for years prior to their joining date.
- **Auto-Approval**: Applying for `complimentary` leave queries the active token queue. If an active token exists, the request is automatically approved, and the token's `used_amount` is set to `1.00`.

### Current Implementation
- Handled dynamically in [User@syncBirthdayCredits](file:///c:/Users/Lenovo/AMS-V1/app/Models/User.php) and [User@getAvailableBirthdayYears](file:///c:/Users/Lenovo/AMS-V1/app/Models/User.php).
  - Unlocks `subDays(config('attendance.birthday_leave_unlock_days', 1))` before birthday.
  - Expires `addYears(config('attendance.birthday_leave_expiry_years', 1))` after unlock.
- The `leave_credits` table stores the tokens. It tracks `source_identifier` (e.g., `birthday_2026`), `unlocked_at`, `expires_at`, and `status`.
- Birthday leave request submissions call `LeaveBalanceService::submitBirthdayLeave` which locks the user and credit, sets `used_amount = 1.00`, sets `is_paid = true` and `metadata = ['is_birthday' => true]`, auto-approves the leave request, and logs a transparent `0.00` ledger entry for audit trail visibility.

---

## 5. Related Modules & Cross References
- **[02_ATTENDANCE_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/02_ATTENDANCE_RULES.md)**: Resolves approved leaves into daily attendance overrides.
- **[04_PAYROLL_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/04_PAYROLL_RULES.md)**: Evaluates unpaid leaves to determine salary deductions.
- **[12_SYSTEM_CONFIGURATION.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/12_SYSTEM_CONFIGURATION.md)**: Configures the monthly accrual rate, negative balance rules, and birthday windows.
