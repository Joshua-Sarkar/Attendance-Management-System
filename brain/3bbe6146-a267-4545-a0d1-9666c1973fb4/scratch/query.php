<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = App\Models\User::all();
echo "Users:\n";
foreach ($users as $u) {
    echo "ID: {$u->id}, Name: {$u->name}, Email: {$u->email}, Role: {$u->role}\n";
}

$records = App\Models\PayrollRecord::all();
echo "\nPayroll Records:\n";
foreach ($records as $r) {
    echo "ID: {$r->id}, User ID: {$r->user_id}, Cycle ID: {$r->payroll_cycle_id}, Review Status: {$r->employee_review_status}, Locked: {$r->locked}\n";
}
