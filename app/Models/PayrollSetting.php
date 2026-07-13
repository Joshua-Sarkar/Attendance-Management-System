<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollSetting extends Model
{
    protected $fillable = ['key', 'value'];

    protected $casts = [
        'value' => 'json',
    ];

    /**
     * Get default settings mapping.
     */
    public static function defaults(): array
    {
        return [
            'lifecycle' => [
                'probationDays' => 90,
                'autoPromote' => true,
                'probationLeaveBalance' => 0,
                'probationPayrollCycle' => '20th-to-20th',
                'payroll_cycle_transition_days' => 120, // BRS §5.3
            ],
            'workWeek' => [
                'workingDays' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                'weeklyOff' => ['Sun'],
                'saturdayIsWorking' => true,
            ],
            'shifts' => [
                ['id' => 'standard', 'label' => 'Standard Shift (Non-IT)', 'type' => 'Fixed Daily Shift', 'start' => '09:30', 'end' => '17:30', 'graceMinutes' => 5, 'lateAfter' => '09:35'],
                ['id' => 'healthcare', 'label' => 'Healthcare Shift', 'type' => 'Flexible Daily Shift', 'start' => '10:00', 'end' => '18:30', 'graceMinutes' => 5, 'lateAfter' => '10:05'],
                ['id' => 'morning', 'label' => 'Morning Shift', 'type' => 'Fixed Daily Shift', 'start' => '09:00', 'end' => '17:30', 'graceMinutes' => 5, 'lateAfter' => '09:05'],
            ],
            'attendance' => [
                'resolutionOrder' => ['Manual Override', 'Birthday Leave', 'Approved Leave', 'Attendance Record', 'Automatic Half Day', 'Weekly Off', 'Future', 'Rejected Leave', 'Absent'],
                'minWorkingHoursForPresent' => 8,
                'halfDayTriggerHours' => 4,
                'autoHalfDayOnLateArrival' => true,
            ],
            'leave' => [
                'planned' => ['paid' => true, 'consumesBalance' => 'planned'],
                'unplanned' => ['paid' => true, 'consumesBalance' => 'unplanned', 'allowFutureDates' => false],
                'birthday' => ['autoApproved' => true, 'eligibleFromDaysBefore' => 1, 'consumesStandardBalance' => false],
                'halfDayPlanned' => ['consumes' => 0.5, 'bucket' => 'planned'],
                'halfDayUnplanned' => ['consumes' => 0.5, 'bucket' => 'unplanned'],
                'monthlyCreditAmount' => 2,
                'monthlyCreditDay' => 1,
                'rejectOverBalanceImmediately' => true,
                'carryForward' => true,
            ],
            'payroll' => [
                'salaryPaymentDay' => 7,
                'permanentCycle' => 'calendar-month',
                'probationCycle' => '20th-to-20th',
                'dailySalaryFormula' => 'monthly / calendar_days_in_month',
                'roundNetTo' => 1,
            ],
            'payrollMapping' => [
                ['state' => 'Present', 'effect' => '100% Daily Pay'],
                ['state' => 'Half Day (Automatic)', 'effect' => '50% Daily Pay'],
                ['state' => 'Half Day Planned', 'effect' => '50% Paid Leave'],
                ['state' => 'Half Day Unplanned Approved', 'effect' => '50% Paid Leave'],
                ['state' => 'Half Day Unplanned Rejected', 'effect' => '50% Deduction'],
                ['state' => 'Absent', 'effect' => '100% Deduction'],
                ['state' => 'Planned Leave', 'effect' => 'Fully Paid'],
                ['state' => 'Birthday Leave', 'effect' => 'Fully Paid'],
                ['state' => 'Weekly Off', 'effect' => 'Fully Paid'],
                ['state' => 'Manual Override', 'effect' => 'Uses Override Result'],
            ],
            'excelImport' => [
                'source' => 'Zimyo Excel Export',
                'twoPassHierarchyResolution' => true,
                'columns' => ['Planned Leave', 'Unplanned Leave', 'Birthday Leave', 'Paternity Leave', 'Maternity Leave', 'Compensatory Leave', 'Pending Leave', 'Carry Forward', 'Utilized Leave', 'Remaining Leave'],
            ],
            'audit' => [
                'retentionYears' => 7,
                'logFieldLevelChanges' => true,
                'requiredFields' => ['Administrator', 'Timestamp', 'Old Value', 'New Value', 'Reason'],
                'appliesTo' => ['Attendance Override', 'Leave Adjustment', 'Payroll Correction', 'Salary Change', 'Employee Edit', 'Shift Change', 'Leave Balance Modification'],
            ],
            'lock' => [
                'excludeUnresolvedFromLock' => true,
                'requireDualSignoffToUnlock' => true,
            ],
            'overtime' => [
                'multiplier' => '1.5',
                'eligibility' => '8',
                'cap' => '30',
            ],
            'pf' => [
                'employee_rate' => '12',
                'employer_rate' => '12',
                'applicable_above_wage_ceiling' => true,
            ],
            'esi' => [
                'eligibility_ceiling' => '21000',
                'employee_rate' => '0.75',
                'employer_rate' => '3.25',
            ],
            'ptax' => [
                'state' => 'Uttarakhand',
                'monthly_professional_tax' => '200',
            ],
        ];
    }

    /**
     * Get setting value by key.
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        if ($setting) {
            return $setting->value;
        }
        
        $defaults = self::defaults();
        return $defaults[$key] ?? $default;
    }

    /**
     * Set setting value by key.
     */
    public static function setValue(string $key, $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Seed default values.
     */
    public static function seedDefaults(): void
    {
        foreach (self::defaults() as $key => $value) {
            self::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
