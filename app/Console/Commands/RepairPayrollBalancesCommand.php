<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\LeaveBalance;
use App\Models\PayrollProfile;
use App\Models\LeaveLedgerEntry;
use PhpOffice\PhpSpreadsheet\IOFactory;

class RepairPayrollBalancesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ams:repair-payroll-balances 
                            {--leave-file= : Path to Leave Balance Excel spreadsheet} 
                            {--salary-file= : Path to Salary Sheet Excel spreadsheet}
                            {--enable-all : Enable payroll_enabled=true for all non-admin employees}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Production repair tool to sync salary profiles, leave balances, and enable payroll participation';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("Starting AMS Payroll & Leave Balance Repair Process...");

        $leaveFile = $this->option('leave-file');
        $salaryFile = $this->option('salary-file');
        $enableAll = $this->option('enable-all');

        $leaveRepairedCount = 0;
        $salaryRepairedCount = 0;
        $payrollEnabledCount = 0;

        // 1. Repair Leave Balances from Excel
        if ($leaveFile) {
            if (!file_exists($leaveFile)) {
                $this->error("Leave Balance file not found: {$leaveFile}");
                return 1;
            }

            $this->info("Processing Leave Balance repair from: {$leaveFile}");
            $leaveRepairedCount = $this->processLeaveExcel($leaveFile);
        }

        // 2. Repair Salary & Enable Payroll from Excel
        if ($salaryFile) {
            if (!file_exists($salaryFile)) {
                $this->error("Salary Sheet file not found: {$salaryFile}");
                return 1;
            }

            $this->info("Processing Salary Sheet repair from: {$salaryFile}");
            $salaryRepairedCount = $this->processSalaryExcel($salaryFile);
        }

        // 3. Align payroll_enabled status for all eligible employees
        // Business Rule: Every employee should participate in payroll unless explicitly excluded.
        if ($enableAll || $salaryFile || !$leaveFile) {
            $payrollEnabledCount = $this->enablePayrollForEligibleEmployees();
        }

        // 4. Safety Reconciliation: Sync user.leave_balance with LeaveBalance.remaining_leave
        $reconciledCount = $this->reconcileLeaveBalances();

        // 5. Execution Summary
        $this->newLine();
        $this->info("=== REPAIR SUMMARY ===");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Leave Balances Repaired', $leaveRepairedCount],
                ['Salary Profiles Updated', $salaryRepairedCount],
                ['Payroll Profiles Enabled', $payrollEnabledCount],
                ['Leave Balances Reconciled', $reconciledCount],
                ['Total Active Employees', User::where('role', '!=', 'admin')->count()],
                ['Total Payroll Enabled', PayrollProfile::where('payroll_enabled', true)->count()],
            ]
        );

        return 0;
    }

    /**
     * Process Leave Balance spreadsheet repair.
     */
    protected function processLeaveExcel(string $filePath): int
    {
        $count = 0;
        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);
            $headers = $rows[1] ?? [];

            $headersMap = [];
            foreach ($headers as $col => $header) {
                if ($header !== null) {
                    $headersMap[strtolower(str_replace([' ', '_', '-', '.', '/'], '', trim($header)))] = $col;
                }
            }

            foreach ($rows as $rowIndex => $row) {
                if ($rowIndex === 1) continue;

                $empCodeCol = $headersMap['employeecode'] ?? null;
                if (!$empCodeCol || empty($row[$empCodeCol])) continue;

                $empCode = trim((string)$row[$empCodeCol]);
                $standardId = 'EMP' . str_pad(preg_replace('/[^0-9]/', '', $empCode), 5, '0', STR_PAD_LEFT);

                $user = User::where('employee_id', $standardId)
                    ->orWhere('employee_id', $empCode)
                    ->first();

                if ($user) {
                    $planned = isset($headersMap['plannedleave']) ? (float)$row[$headersMap['plannedleave']] : 0.00;
                    $unplanned = isset($headersMap['unplannedleave']) ? (float)$row[$headersMap['unplannedleave']] : 0.00;
                    $paternity = isset($headersMap['paternityleave']) ? (float)$row[$headersMap['paternityleave']] : 0.00;
                    $maternity = isset($headersMap['maternityleave']) ? (float)$row[$headersMap['maternityleave']] : 0.00;
                    $compensatory = isset($headersMap['compensatoryleave']) ? (float)$row[$headersMap['compensatoryleave']] : 0.00;
                    $pending = isset($headersMap['totalpending']) ? (float)$row[$headersMap['totalpending']] : 0.00;
                    $utilized = isset($headersMap['utilizedappliedleave']) ? (float)$row[$headersMap['utilizedappliedleave']] : 0.00;
                    $carryForward = isset($headersMap['totalcarryforward']) ? (float)$row[$headersMap['totalcarryforward']] : 0.00;
                    $remaining = isset($headersMap['totalremaining']) ? (float)$row[$headersMap['totalremaining']] : 0.00;

                    $leaveBalance = LeaveBalance::firstOrCreate(['user_id' => $user->id]);
                    $oldRemaining = (float)$leaveBalance->remaining_leave;

                    $leaveBalance->update([
                        'planned_leave' => $planned,
                        'unplanned_leave' => $unplanned,
                        'paternity_leave' => $paternity,
                        'maternity_leave' => $maternity,
                        'compensatory_leave' => $compensatory,
                        'pending_leave' => $pending,
                        'utilized_leave' => $utilized,
                        'carry_forward' => $carryForward,
                        'remaining_leave' => $remaining,
                        'last_imported_at' => now(),
                        'import_source' => 'Artisan Repair',
                    ]);

                    $user->leave_balance = $remaining;
                    $user->save();

                    $diff = $remaining - $oldRemaining;
                    if ((float)$diff !== 0.0) {
                        LeaveLedgerEntry::create([
                            'user_id' => $user->id,
                            'amount' => $diff,
                            'type' => 'adjustment',
                            'description' => "Remaining Leave repaired via command: {$oldRemaining} -> {$remaining}",
                        ]);
                    }

                    $count++;
                }
            }
        } catch (\Exception $e) {
            $this->error("Error reading Leave Balance spreadsheet: " . $e->getMessage());
        }

        return $count;
    }

    /**
     * Process Salary Sheet spreadsheet repair.
     */
    protected function processSalaryExcel(string $filePath): int
    {
        $count = 0;
        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);
            $headers = $rows[1] ?? [];

            $headersMap = [];
            foreach ($headers as $col => $header) {
                if ($header !== null) {
                    $headersMap[strtolower(str_replace([' ', '_', '-', '.', '/'], '', trim($header)))] = $col;
                }
            }

            foreach ($rows as $rowIndex => $row) {
                if ($rowIndex === 1) continue;

                $empCodeCol = $headersMap['empid'] ?? ($headersMap['employeecode'] ?? null);
                if (!$empCodeCol || empty($row[$empCodeCol])) continue;

                $empCode = trim((string)$row[$empCodeCol]);
                $standardId = 'EMP' . str_pad(preg_replace('/[^0-9]/', '', $empCode), 5, '0', STR_PAD_LEFT);

                $user = User::where('employee_id', $standardId)
                    ->orWhere('employee_id', $empCode)
                    ->first();

                if ($user) {
                    $salaryVal = isset($headersMap['empsalary']) ? $row[$headersMap['empsalary']] : ($headersMap['basesalary'] ?? null ? $row[$headersMap['basesalary']] : null);
                    if ($salaryVal !== null) {
                        $salary = (float)preg_replace('/[^0-9.-]/', '', $salaryVal);
                        $payrollProfile = PayrollProfile::firstOrCreate(['user_id' => $user->id]);

                        $effectiveDate = $user->joining_date ? $user->joining_date->format('Y-m-d') : ($payrollProfile->salary_effective_date ? $payrollProfile->salary_effective_date->format('Y-m-d') : '2026-06-01');

                        $payrollProfile->update([
                            'base_salary' => $salary,
                            'salary_effective_date' => $effectiveDate,
                            'payroll_enabled' => true,
                            'last_imported_at' => now(),
                            'import_source' => 'Artisan Repair',
                        ]);

                        if ($payrollProfile->salaryHistories()->count() <= 1) {
                            $payrollProfile->salaryHistories()->delete();
                            $payrollProfile->recordSalaryRevision(
                                $salary,
                                $effectiveDate,
                                'Salary setup via repair command',
                                null,
                                'Artisan Repair'
                            );
                        }

                        $count++;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->error("Error reading Salary Sheet spreadsheet: " . $e->getMessage());
        }

        return $count;
    }

    /**
     * Enable payroll participation for all non-admin employees according to business rules.
     */
    protected function enablePayrollForEligibleEmployees(): int
    {
        $count = 0;
        $users = User::where('role', '!=', 'admin')->get();

        foreach ($users as $user) {
            $profile = PayrollProfile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'base_salary' => 30000.00,
                    'salary_effective_date' => $user->joining_date ? $user->joining_date->format('Y-m-d') : '2026-06-01',
                    'payroll_enabled' => true,
                    'import_source' => 'Business Rule Sync',
                ]
            );

            if (!$profile->payroll_enabled) {
                $effectiveDate = $user->joining_date ? $user->joining_date->format('Y-m-d') : ($profile->salary_effective_date ? $profile->salary_effective_date->format('Y-m-d') : '2026-06-01');
                
                $profile->update([
                    'payroll_enabled' => true,
                    'base_salary' => $profile->base_salary ?: 30000.00,
                    'salary_effective_date' => $effectiveDate,
                ]);

                if ($profile->salaryHistories()->count() === 0) {
                    $profile->recordSalaryRevision(
                        (float)$profile->base_salary,
                        $effectiveDate,
                        'Initial salary placement',
                        null,
                        'Business Rule Sync'
                    );
                }

                $count++;
            }
        }

        return $count;
    }

    /**
     * Reconcile leave balances between User model and LeaveBalance model.
     */
    protected function reconcileLeaveBalances(): int
    {
        $count = 0;
        $balances = LeaveBalance::all();

        foreach ($balances as $lb) {
            $user = $lb->user;
            if ($user && (float)$user->leave_balance !== (float)$lb->remaining_leave) {
                $user->leave_balance = $lb->remaining_leave;
                $user->saveQuietly();
                $count++;
            }
        }

        return $count;
    }
}
