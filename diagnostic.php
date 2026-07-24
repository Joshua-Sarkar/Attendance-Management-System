<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\AttendanceService;
use Carbon\Carbon;

$service = app(AttendanceService::class);

$employee = \App\Models\User::where('role', 'employee')->first();

if (!$employee) {
    echo "Error: Need at least one employee in the database.\n";
    exit(1);
}

$employee->update(['joining_date' => '2026-04-01']);
$employee->refresh();

echo "Employee: " . $employee->name . " (ID: " . $employee->id . ", Joined: " . $employee->joining_date->format('Y-m-d') . ")\n";


// 1. Resolve Cycle Info
$resolver = new \App\Services\PayrollCycleResolver();
$cycleInfo = $resolver->resolve($employee, 2026, 6);

if ($cycleInfo) {
    echo "1. Cycle range resolved:\n";
    echo "   Start: " . $cycleInfo['start_date']->format('Y-m-d') . "\n";
    echo "   End: " . $cycleInfo['end_date']->format('Y-m-d') . "\n";
    
    $start = $cycleInfo['start_date'];
    $end = $cycleInfo['end_date'];
    
    // Check if 20 June is in the resolved range
    $targetDate = \Carbon\Carbon::parse('2026-06-20')->startOfDay();
    $inRange = $targetDate->between($start, $end);
    echo "   Is 20 Jun in cycle range? " . ($inRange ? "YES" : "NO") . "\n";
    
    // 2. Fetch attendance states for range
    $attendanceService = app(\App\Services\AttendanceService::class);
    $states = $attendanceService->getAttendanceStatesForRange($employee, $start, $end);
    
    echo "2. Attendance states returned:\n";
    echo "   Count: " . count($states) . "\n";
    echo "   Is 20 Jun in Attendance states keys? " . (isset($states['2026-06-20']) ? "YES" : "NO") . "\n";
    
    // 3. Process daily breakdown in calculateMonthlyPayroll
    $payroll = \App\Services\PayrollService::calculateMonthlyPayroll($employee, 2026, 6);
    $breakdown = $payroll['daily_breakdown'] ?? [];
    
    echo "3. Payroll Daily Breakdown:\n";
    echo "   Is 20 Jun in daily_breakdown keys? " . (isset($breakdown['2026-06-20']) ? "YES" : "NO") . "\n";
    if (isset($breakdown['2026-06-20'])) {
        echo "   20 Jun Breakdown: " . json_encode($breakdown['2026-06-20']) . "\n";
    }
} else {
    echo "Cycle info not resolved.\n";
}



