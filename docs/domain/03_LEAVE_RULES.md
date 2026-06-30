# 03. Leave & Ledger Rules

This document details the business rules and ledger controls for planned, unplanned, and complimentary leave types, monthly accruals, and special birthday leave tokens.

---

## 1. Leave Categories & Eligibility Rules

### Intended Business Rule
The system supports three categories of leave:
1. **Planned Leave**: Scheduled holidays applied for in advance. Requires manager approval. Deducted from leave balance if approved.
2. **Unplanned Leave**: Emergency time-off. Applied for retroactively or on short notice. Requires manager approval. Deducted from leave balance if approved.
3. **Complimentary (Birthday) Leave**: A special 1-day holiday allocated annually around the employee's birthday. It does not deduct from the standard leave balance and is auto-approved if active.

### Current Implementation
- Handled in [LeaveRequestController@store](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/LeaveRequestController.php#L103-L161).
- Overlap checking is performed on submission to prevent multiple active requests on the same calendar dates.
- Standard employees must specify a reason (minimum 5 characters) and select one of the three categories.

### Known Inconsistencies
- General employees can request any start date (including past dates for unplanned leaves), but there is no block preventing them from submitting a "Planned" leave in the past, only an `after_or_equal:today` validation rule on the request form, which blocks retroactive planned leaves, but allows retroactive unplanned leaves if validation is bypassed.

---

## 2. Double-Entry Leave Ledger Rules

### Intended Business Rule
- **Transactional Balances**: The user's `leave_balance` must never be modified directly. Every adjustment must be recorded as a row in the `leave_ledger_entries` audit trail. The `leave_balance` in the `users` table is a cached summary of the ledger sum.
- **Deduction and Refund**:
  - When a manager approves a Planned or Unplanned leave, the system deducts the `total_days` from `users.leave_balance` and records a ledger row of type `'deduction'` with a negative amount.
  - If a user cancels an approved paid leave, the balance must be restored, and a ledger row of type `'refund'` with a positive amount is created.
  - Administrative adjustments must be logged under the `'adjustment'` type.
- **Concurrency Protections**: The system must serialize balance updates using pessimistic database locks (`lockForUpdate()`) to prevent concurrent approvals or cancels from resulting in duplicate deductions (double-spend balance anomalies).

### Current Implementation
- All balance deductions and refunds are run inside database transactions wrapped in `DB::transaction()` with a blocking row lock:
  `User::where('id', $userId)->lockForUpdate()->firstOrFail();`
- Processed in [LeaveRequestController@approve](file:///c:/Users/Lenovo/AMS-V1/app/Http/Controllers/LeaveRequestController.php#L291-L320) and `cancel` / `override` actions.

### Known Inconsistencies
- **Cached Balance Drift**: If an error occurs where a ledger entry is written but the `users.leave_balance` column is not updated (or vice-versa), they will drift. There is no automated reconciliation tool or nightly cron to recalculate `leave_balance` from the sum of `leave_ledger_entries`.

---

## 3. Leave Accruals & Idempotency Rules

### Intended Business Rule
- **Initial Grant**: New employees receive an opening balance of 2.00 days upon creation.
- **Monthly Accrual**: On the 1st of every month, all active employees are credited with 2.00 additional leave days.
- **Idempotency Guard**: The monthly accrual job must be idempotent. If run multiple times in the same calendar month, it must skip users who have already received their accrual for that month.

### Current Implementation
- **Initializer**: [LeaveBalanceService::initializeUser](file:///c:/Users/Lenovo/AMS-V1/app/Services/LeaveBalanceService.php#L89-L102) sets the initial 2.00 balance and writes an `'opening_balance'` ledger entry.
- **Accrual Command**: Run via [AccrueLeavesCommand](file:///c:/Users/Lenovo/AMS-V1/app/Console/Commands/AccrueLeavesCommand.php) (`leaves:accrue`). It checks for `accrual` ledger entries in the current month for each user. If none exist, it credits 2.00 days and logs the accrual entry.

---

## 4. Birthday Leave & Token Rules

### Intended Business Rule
- **Complimentary Credit**: Eligible employees receive a 1.00-day birthday leave token.
- **Unlock Window**: The token is unlocked and synced exactly one day before the employee's birthday.
- **Validity & Expiry**: The token is valid for exactly one year from its unlock date. If unused, it is marked as `expired` and cannot be claimed.
- **Leap Year Rule**: If an employee is born on February 29 (leap year), in non-leap years their birthday is resolved to February 27. The token unlocks on February 26 and expires on February 26 of the following year.
- **Eligibility (Tenure)**: Employees cannot claim birthday leaves for years prior to their joining date.
- **Auto-Approval**: Applying for `complimentary` leave queries the active token queue. If an active token exists, the request is automatically approved, and the token's `used_amount` is set to `1.00`.

### Current Implementation
- Handled dynamically in [User@syncBirthdayCredits](file:///c:/Users/Lenovo/AMS-V1/app/Models/User.php#L96-L158) and [User@getAvailableBirthdayYears](file:///c:/Users/Lenovo/AMS-V1/app/Models/User.php#L163-L222).
- The `leave_credits` table stores the tokens. It tracks `source_identifier` (e.g., `birthday_2026`), `unlocked_at`, `expires_at`, and `status`.
- Birthday leave request submissions call `LeaveBalanceService::submitBirthdayLeave` which locks the user and credit, sets `used_amount = 1.00`, and auto-approves the leave request.

---

## 5. Related Modules & Cross References
- **[02_ATTENDANCE_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/02_ATTENDANCE_RULES.md)**: Resolves approved leaves into daily attendance overrides.
- **[04_PAYROLL_RULES.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/04_PAYROLL_RULES.md)**: Evaluates unpaid leaves to determine salary deductions.
- **[12_SYSTEM_CONFIGURATION.md](file:///c:/Users/Lenovo/AMS-V1/docs/domain/12_SYSTEM_CONFIGURATION.md)**: Configures the monthly accrual rate and annual targets.
