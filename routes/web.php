<?php
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DepartmentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {

    Route::get('/departments', [DepartmentController::class, 'index'])
        ->name('departments.index');

    Route::get('/departments/create', [DepartmentController::class, 'create'])
        ->name('departments.create');

    Route::post('/departments', [DepartmentController::class, 'store'])
        ->name('departments.store');

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
});

Route::get('/employees/{user}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');

Route::put('/employees/{user}', [EmployeeController::class, 'update'])->name('employees.update');

Route::delete('/employees/{user}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
Route::get('/employees/{user}/edit', [EmployeeController::class, 'edit'])
    ->name('employees.edit');

Route::put('/employees/{user}', [EmployeeController::class, 'update'])
    ->name('employees.update');

Route::delete('/employees/{user}', [EmployeeController::class, 'destroy'])
    ->name('employees.destroy');

require __DIR__.'/auth.php';