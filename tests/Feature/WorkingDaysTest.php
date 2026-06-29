<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WorkingDaysTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that Saturday is a working day (absent fallback) and Sunday is a weekend.
     */
    public function test_saturday_is_working_day_and_sunday_is_weekend(): void
    {
        $employee = User::factory()->create([
            'role' => 'employee',
            'status' => 'active',
            'must_change_password' => false,
        ]);

        // Fix today's date to a known Sunday: 2026-06-21 (Sunday)
        Carbon::setTestNow(Carbon::parse('2026-06-21 12:00:00'));

        $attendanceController = resolve(\App\Http\Controllers\AttendanceController::class);
        $request = new \Illuminate\Http\Request();
        $request->setUserResolver(fn() => $employee);

        $view = $attendanceController->myAttendance($request);
        $history = $view->getData()['history'];

        // Let's find June 20th (Saturday) and June 21st (Sunday) in the history
        $saturdayEntry = collect($history)->first(fn($h) => $h['date']->format('Y-m-d') === '2026-06-20');
        $sundayEntry = collect($history)->first(fn($h) => $h['date']->format('Y-m-d') === '2026-06-21');

        $this->assertNotNull($saturdayEntry, 'Saturday entry should exist in 30-day history');
        $this->assertNotNull($sundayEntry, 'Sunday entry should exist in 30-day history');

        // Saturday should NOT be marked as weekend, and should fall back to 'absent' if no check-in
        $this->assertFalse($saturdayEntry['is_weekend']);
        $this->assertEquals('absent', $saturdayEntry['status']);

        // Sunday should be marked as weekend, and should fall back to 'weekly_off' if no check-in
        $this->assertTrue($sundayEntry['is_weekend']);
        $this->assertEquals('weekly_off', $sundayEntry['status']);

        // Let's verify getEmployeeStats includes Saturday but skips Sunday
        $attendanceService = resolve(AttendanceService::class);
        $stats = $attendanceService->getEmployeeStats($employee, 30);

        // Count non-Sundays in the last 30 days dynamically
        $expectedAbsent = 0;
        $startDate = today()->subDays(29);
        for ($i = 0; $i < 30; $i++) {
            if (!$startDate->copy()->addDays($i)->isSunday()) {
                $expectedAbsent++;
            }
        }

        $this->assertEquals($expectedAbsent, $stats['absent'], 'Employee should be absent on all Saturdays and other working days');
        $this->assertEquals(0, $stats['present']);
        $this->assertEquals(0, $stats['late']);

        Carbon::setTestNow(); // Reset test time
    }
}
