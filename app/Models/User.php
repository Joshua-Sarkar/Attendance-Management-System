<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'employee_id',
        'name',
        'email',
        'phone',
        'password',
        'role',
        'status',
        'leave_balance',
        'joining_date',
        'must_change_password',
        'department_id',
        'manager_id',
        'admin_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'joining_date' => 'date',
            'must_change_password' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function directReports(): HasMany
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function employeeProfile(): HasOne
    {
        return $this->hasOne(EmployeeProfile::class);
    }

    public function leaveLedgerEntries(): HasMany
    {
        return $this->hasMany(LeaveLedgerEntry::class);
    }

    public function leaveCredits(): HasMany
    {
        return $this->hasMany(LeaveCredit::class);
    }

    /**
     * Synchronize birthday leave credits for the user.
     */
    public function syncBirthdayCredits(\Carbon\Carbon $date = null): void
    {
        $date = $date ? $date->copy()->startOfDay() : \Carbon\Carbon::today();

        $profile = $this->employeeProfile;
        if (!$profile || !$profile->date_of_birth) {
            return;
        }

        $dob = \Carbon\Carbon::parse($profile->date_of_birth);
        $birthMonth = $dob->month;
        $birthDay = $dob->day;

        $referenceYear = $date->year;
        $yearsToCheck = [$referenceYear - 1, $referenceYear, $referenceYear + 1];

        foreach ($yearsToCheck as $year) {
            // Handle leap year (Feb 29) birthdays on non-leap years
            $isLeapYear = (($year % 4 == 0) && ($year % 100 != 0)) || ($year % 400 == 0);
            if ($birthMonth === 2 && $birthDay === 29 && !$isLeapYear) {
                $birthday = \Carbon\Carbon::create($year, 2, 27)->startOfDay();
            } else {
                $birthday = \Carbon\Carbon::create($year, $birthMonth, $birthDay)->startOfDay();
            }

            $unlockDate = $birthday->copy()->subDay()->startOfDay();
            $expiryDate = $unlockDate->copy()->addYear()->endOfDay();

            // If we are on or after the unlock date, check/create the credit
            if ($date->greaterThanOrEqualTo($unlockDate)) {
                $joiningYear = $profile->joining_date ? \Carbon\Carbon::parse($profile->joining_date)->year : null;
                if ($joiningYear && $year < $joiningYear) {
                    continue;
                }

                $identifier = "birthday_{$year}";

                $credit = LeaveCredit::firstOrCreate(
                    [
                        'user_id' => $this->id,
                        'source_identifier' => $identifier,
                    ],
                    [
                        'credit_type' => 'birthday',
                        'amount' => 1.00,
                        'used_amount' => 0.00,
                        'status' => 'active',
                        'unlocked_at' => $unlockDate,
                        'expires_at' => $expiryDate,
                        'source_metadata' => [
                            'date_of_birth' => $dob->format('Y-m-d'),
                            'cycle_year' => $year,
                        ],
                    ]
                );

                // Auto-expire credit if past expires_at and still active/unused
                if ($date->greaterThan($expiryDate) && $credit->status === 'active' && $credit->used_amount < $credit->amount) {
                    $credit->update(['status' => 'expired']);
                }
            }
        }
    }

    /**
     * Get list of currently active and unused birthday years.
     */
    public function getAvailableBirthdayYears(\Carbon\Carbon $date = null): array
    {
        $date = $date ? $date->copy()->startOfDay() : \Carbon\Carbon::today();

        // Self-heal: ensure credits are synced up to date
        $this->syncBirthdayCredits($date);

        $profile = $this->employeeProfile;
        if (!$profile || !$profile->date_of_birth) {
            return [];
        }

        $dob = \Carbon\Carbon::parse($profile->date_of_birth);
        $birthMonth = $dob->month;
        $birthDay = $dob->day;

        $referenceYear = $date->year;
        $yearsToCheck = [$referenceYear - 1, $referenceYear, $referenceYear + 1];
        $availableYears = [];

        foreach ($yearsToCheck as $year) {
            $isLeapYear = (($year % 4 == 0) && ($year % 100 != 0)) || ($year % 400 == 0);
            if ($birthMonth === 2 && $birthDay === 29 && !$isLeapYear) {
                $birthday = \Carbon\Carbon::create($year, 2, 27)->startOfDay();
            } else {
                $birthday = \Carbon\Carbon::create($year, $birthMonth, $birthDay)->startOfDay();
            }

            $unlockDate = $birthday->copy()->subDay()->startOfDay();
            $expiryDate = $unlockDate->copy()->addYear()->endOfDay();

            $joiningYear = $profile->joining_date ? \Carbon\Carbon::parse($profile->joining_date)->year : null;
            if ($joiningYear && $year < $joiningYear) {
                continue;
            }

            if ($date->greaterThanOrEqualTo($unlockDate) && $date->lessThanOrEqualTo($expiryDate)) {
                // Find if there is an active unused credit in DB
                $credit = LeaveCredit::where('user_id', $this->id)
                    ->where('source_identifier', "birthday_{$year}")
                    ->where('status', 'active')
                    ->whereRaw('used_amount < amount')
                    ->first();

                if ($credit) {
                    $availableYears[] = [
                        'year' => $year,
                        'credit_id' => $credit->id,
                        'unlock_date' => $unlockDate,
                        'expiry_date' => $expiryDate,
                    ];
                }
            }
        }

        // Sort by year ascending to consume the oldest credit first
        usort($availableYears, fn($a, $b) => $a['year'] <=> $b['year']);

        return $availableYears;
    }
}