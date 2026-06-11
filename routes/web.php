<?php
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ManagerAttendanceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {

    Route::get('/departments', [DepartmentController::class, 'index'])
        ->name('departments.index');

    Route::get('/departments/create', [DepartmentController::class, 'create'])
        ->name('departments.create');

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

    Route::get('/employees', [EmployeeController::class, 'index'])
        ->name('employees.index');

    Route::get('/employees/create', [EmployeeController::class, 'create'])
        ->name('employees.create');

    Route::post('/employees', [EmployeeController::class, 'store'])
        ->name('employees.store');

    Route::get('/employees/{user}/edit', [EmployeeController::class, 'edit'])
        ->name('employees.edit');

    Route::put('/employees/{user}', [EmployeeController::class, 'update'])
        ->name('employees.update');

    Route::delete('/employees/{user}', [EmployeeController::class, 'destroy'])
        ->name('employees.destroy');

    // Attendance routes
    Route::get('/employee/dashboard', [AttendanceController::class, 'employeeDashboard'])
        ->name('employee.dashboard');

    Route::get('/my-attendance', [AttendanceController::class, 'myAttendance'])
        ->name('attendance.my-attendance');

    Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn'])
        ->name('attendance.check-in');

    Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut'])
        ->name('attendance.check-out');

    Route::get('/attendance/history', [AttendanceController::class, 'history'])
        ->name('attendance.history');

    Route::get('/admin/attendance/employee/{user}', [ManagerAttendanceController::class, 'show'])
        ->name('admin.attendance.employee.show');
});

require __DIR__.'/auth.php';