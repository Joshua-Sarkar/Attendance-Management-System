<?php
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ManagerAttendanceController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\AttendanceAuditController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {

    Route::get('/departments', [DepartmentController::class, 'index'])
        ->name('departments.index');

    Route::get('/departments/create', [DepartmentController::class, 'create'])
        ->name('departments.create');

    Route::get('/departments/{department}', [DepartmentController::class, 'show'])
        ->name('departments.show');

    Route::post('/departments', [DepartmentController::class, 'store'])
        ->name('departments.store');

    Route::get('/departments/{department}/edit', [DepartmentController::class, 'edit'])
        ->name('departments.edit');

    Route::put('/departments/{department}', [DepartmentController::class, 'update'])
        ->name('departments.update');

    Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])
        ->name('departments.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

    Route::resource('employees', EmployeeController::class)->parameters([
        'employees' => 'user',
    ]);

    // Attendance routes
    Route::get('/employee/dashboard', [AttendanceController::class, 'employeeDashboard'])
        ->name('employee.dashboard');

    Route::get('/my-attendance', [AttendanceController::class, 'myAttendance'])
        ->name('attendance.my-attendance');

    Route::get('/attendance/calendar/data', [\App\Http\Controllers\EmployeeAttendanceCalendarController::class, 'getData'])
        ->name('attendance.calendar.data');

    Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn'])
        ->name('attendance.check-in');

    Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut'])
        ->name('attendance.check-out');

    Route::get('/attendance/history', [AttendanceController::class, 'history'])
        ->name('attendance.history');

    Route::get('/admin/attendance/employee/{user}', [ManagerAttendanceController::class, 'show'])
        ->name('admin.attendance.employee.show');

    // Attendance Ledger Routes
    Route::get('/admin/attendance-ledger', [\App\Http\Controllers\AttendanceLedgerController::class, 'index'])
        ->name('admin.attendance.ledger');
    Route::get('/admin/attendance-ledger/dossier', [\App\Http\Controllers\AttendanceLedgerController::class, 'dossier'])
        ->name('admin.attendance.ledger.dossier');
    Route::post('/admin/attendance-ledger/override', [\App\Http\Controllers\AttendanceLedgerController::class, 'override'])
        ->name('admin.attendance.ledger.override');
    Route::post('/admin/attendance-ledger/bulk-override', [\App\Http\Controllers\AttendanceLedgerController::class, 'bulkOverride'])
        ->name('admin.attendance.ledger.bulk-override');
    Route::post('/admin/attendance-ledger/bulk-preview', [\App\Http\Controllers\AttendanceLedgerController::class, 'bulkPreview'])
        ->name('admin.attendance.ledger.bulk-preview');
    Route::post('/admin/attendance-ledger/assign-leave', [\App\Http\Controllers\AttendanceLedgerController::class, 'assignLeave'])
        ->name('admin.attendance.ledger.assign-leave');
    Route::post('/admin/attendance-ledger/change-shift', [\App\Http\Controllers\AttendanceLedgerController::class, 'changeShift'])
        ->name('admin.attendance.ledger.change-shift');

    // Leaves Management Routes
    Route::get('/leaves', [LeaveRequestController::class, 'index'])->name('leaves.index');
    Route::get('/leaves/create', [LeaveRequestController::class, 'create'])->name('leaves.create');
    Route::post('/leaves', [LeaveRequestController::class, 'store'])->name('leaves.store');
    Route::get('/leaves/{leaveRequest}', [LeaveRequestController::class, 'show'])->name('leaves.show');
    Route::post('/leaves/{leaveRequest}/cancel', [LeaveRequestController::class, 'cancel'])->name('leaves.cancel');
    Route::post('/leaves/{leaveRequest}/approve', [LeaveRequestController::class, 'approve'])->name('leaves.approve');
    Route::post('/leaves/{leaveRequest}/reject', [LeaveRequestController::class, 'reject'])->name('leaves.reject');
    Route::post('/leaves/{leaveRequest}/override', [LeaveRequestController::class, 'override'])->name('leaves.override');

    // Employee self-service payroll routes
    Route::get('/my-payroll', [\App\Http\Controllers\EmployeePayrollController::class, 'index'])->name('employee.payroll.index');
    Route::post('/my-payroll/approve', [\App\Http\Controllers\EmployeePayrollController::class, 'approve'])->name('employee.payroll.approve');
    Route::post('/my-payroll/dispute', [\App\Http\Controllers\EmployeePayrollController::class, 'dispute'])->name('employee.payroll.dispute');
    Route::get('/my-payslip/{id}/download', [\App\Http\Controllers\EmployeePayrollController::class, 'downloadPayslip'])->name('employee.payslip.download');

    // Admin Employee Import routes
    Route::middleware('admin')->group(function () {
        Route::get('/admin/import-employees', [ImportController::class, 'showUploadForm'])->name('admin.import.show');
        Route::post('/admin/import-employees', [ImportController::class, 'handleUpload'])->name('admin.import.handle');
        Route::post('/admin/import-employees/confirm', [ImportController::class, 'confirmImport'])->name('admin.import.confirm');
        Route::get('/admin/employees/search', [ImportController::class, 'searchEmployees'])->name('admin.employees.search');
        Route::post('/admin/employees/{user}/reset-password', [EmployeeController::class, 'resetPassword'])->name('admin.employees.reset-password');
        Route::get('/admin/attendance-logs', [AttendanceAuditController::class, 'index'])->name('admin.attendance.logs');
        Route::get('/admin/attendance/overrides/employees', [\App\Http\Controllers\AttendanceOverrideController::class, 'employees'])->name('admin.attendance.override.employees');
        Route::post('/admin/attendance/overrides/preview', [\App\Http\Controllers\AttendanceOverrideController::class, 'preview'])->name('admin.attendance.override.preview');
        Route::post('/admin/attendance/overrides', [\App\Http\Controllers\AttendanceOverrideController::class, 'store'])->name('admin.attendance.override.store');

        // Payroll Control Center Routes
        Route::get('/admin/payroll', [\App\Http\Controllers\PayrollController::class, 'index'])->name('admin.payroll.index');
        Route::get('/admin/payroll/dashboard', [\App\Http\Controllers\PayrollController::class, 'index'])->name('admin.payroll.dashboard');
        Route::get('/admin/payroll/employees', [\App\Http\Controllers\PayrollController::class, 'index'])->name('admin.payroll.employees');
        Route::get('/admin/payroll/ledger', [\App\Http\Controllers\PayrollController::class, 'index'])->name('admin.payroll.ledger');
        Route::get('/admin/payroll/corrections', [\App\Http\Controllers\PayrollController::class, 'index'])->name('admin.payroll.corrections');
        Route::get('/admin/payroll/exceptions', [\App\Http\Controllers\PayrollController::class, 'index'])->name('admin.payroll.exceptions');
        Route::get('/admin/payroll/lock', [\App\Http\Controllers\PayrollController::class, 'index'])->name('admin.payroll.lock');
        Route::get('/admin/payroll/payslips', [\App\Http\Controllers\PayrollController::class, 'index'])->name('admin.payroll.payslips');
        Route::get('/admin/payroll/audit', [\App\Http\Controllers\PayrollController::class, 'index'])->name('admin.payroll.audit');
        Route::get('/admin/payroll/reports', [\App\Http\Controllers\PayrollController::class, 'index'])->name('admin.payroll.reports');
        Route::get('/admin/payroll/settings', [\App\Http\Controllers\PayrollController::class, 'index'])->name('admin.payroll.settings');
        Route::post('/admin/payroll/process', [\App\Http\Controllers\PayrollController::class, 'process'])->name('admin.payroll.process');
        Route::post('/admin/payroll/lock', [\App\Http\Controllers\PayrollController::class, 'lock'])->name('admin.payroll.lock');
        Route::post('/admin/payroll/unlock', [\App\Http\Controllers\PayrollController::class, 'unlock'])->name('admin.payroll.unlock');
        Route::post('/admin/payroll/corrections', [\App\Http\Controllers\PayrollController::class, 'correctionStore'])->name('admin.payroll.corrections.store');
        Route::post('/admin/payroll/corrections/{id}/approve', [\App\Http\Controllers\PayrollController::class, 'correctionApprove'])->name('admin.payroll.corrections.approve');
        Route::post('/admin/payroll/settings', [\App\Http\Controllers\PayrollController::class, 'settingsUpdate'])->name('admin.payroll.settings.update');
        Route::get('/admin/payroll/ledger/export', [\App\Http\Controllers\PayrollController::class, 'exportLedger'])->name('admin.payroll.ledger.export');
        Route::post('/admin/payroll/reports/export', [\App\Http\Controllers\PayrollController::class, 'exportReport'])->name('admin.payroll.reports.export');

        // New interactive actions
        Route::post('/admin/payroll/records/{id}/approve', [\App\Http\Controllers\PayrollController::class, 'recordApprove'])->name('admin.payroll.records.approve');
        Route::post('/admin/payroll/records/{id}/lock', [\App\Http\Controllers\PayrollController::class, 'recordLock'])->name('admin.payroll.records.lock');
        Route::post('/admin/payroll/records/{id}/unlock', [\App\Http\Controllers\PayrollController::class, 'recordUnlock'])->name('admin.payroll.records.unlock');
        Route::post('/admin/payroll/disputes/{id}/resolve', [\App\Http\Controllers\PayrollController::class, 'disputeResolve'])->name('admin.payroll.disputes.resolve');
        Route::post('/admin/payroll/records/{id}/payslip/generate', [\App\Http\Controllers\PayrollController::class, 'payslipGenerate'])->name('admin.payroll.payslips.generate');
        Route::post('/admin/payroll/records/{id}/payslip/publish', [\App\Http\Controllers\PayrollController::class, 'payslipPublish'])->name('admin.payroll.payslips.publish');
        Route::post('/admin/payroll/payslips/bulk-generate', [\App\Http\Controllers\PayrollController::class, 'payslipBulkGenerate'])->name('admin.payroll.payslips.bulk-generate');
        Route::post('/admin/payroll/payslips/bulk-publish', [\App\Http\Controllers\PayrollController::class, 'payslipBulkPublish'])->name('admin.payroll.payslips.bulk-publish');
        Route::post('/admin/payroll/settings/preview', [\App\Http\Controllers\PayrollController::class, 'settingsPreview'])->name('admin.payroll.settings.preview');
        Route::post('/admin/payroll/settings/save-recalculate', [\App\Http\Controllers\PayrollController::class, 'settingsSaveRecalculate'])->name('admin.payroll.settings.save-recalculate');
    });

    // Profile Correction Requests Routes
    Route::post('/employee/correction-requests', [\App\Http\Controllers\ProfileCorrectionRequestController::class, 'store'])
        ->name('employee.corrections.store');

    Route::middleware('admin')->group(function () {
        Route::get('/admin/correction-requests', [\App\Http\Controllers\ProfileCorrectionRequestController::class, 'adminIndex'])
            ->name('admin.corrections.index');
        Route::post('/admin/correction-requests/{correctionRequest}/resolve', [\App\Http\Controllers\ProfileCorrectionRequestController::class, 'adminResolve'])
            ->name('admin.corrections.resolve');

        // Admin Corrections Store, Update, Destroy
        Route::post('/admin/employees/{user}/corrections', [\App\Http\Controllers\ProfileCorrectionRequestController::class, 'adminStore'])
            ->name('admin.corrections.store');
        Route::put('/admin/corrections/{correctionRequest}', [\App\Http\Controllers\ProfileCorrectionRequestController::class, 'adminUpdate'])
            ->name('admin.corrections.update');
        Route::delete('/admin/corrections/{correctionRequest}', [\App\Http\Controllers\ProfileCorrectionRequestController::class, 'adminDestroy'])
            ->name('admin.corrections.destroy');

        // Admin Timeline Entry Store, Update, Destroy
        Route::post('/admin/employees/{user}/timeline', [\App\Http\Controllers\EmployeeController::class, 'storeTimelineEntry'])
            ->name('admin.timeline.store');
        Route::put('/admin/timeline/{entry}', [\App\Http\Controllers\EmployeeController::class, 'updateTimelineEntry'])
            ->name('admin.timeline.update');
        Route::delete('/admin/timeline/{entry}', [\App\Http\Controllers\EmployeeController::class, 'destroyTimelineEntry'])
            ->name('admin.timeline.destroy');
    });
});

require __DIR__ . '/auth.php';