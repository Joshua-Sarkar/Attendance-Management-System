<?php

return [
    'start_time' => env('ATTENDANCE_START_TIME', '09:30'),
    'grace_minutes' => (int) env('ATTENDANCE_GRACE_MINUTES', 15),
    'leave_annual_allocation' => (int) env('LEAVE_ANNUAL_ALLOCATION', 24),
    'leave_monthly_accrual_rate' => (int) env('LEAVE_MONTHLY_ACCRUAL_RATE', 2),
    'new_rules_start_date' => env('ATTENDANCE_NEW_RULES_START_DATE'),
];
