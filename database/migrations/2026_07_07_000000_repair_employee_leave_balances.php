<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\LeaveBalance;
use App\Models\PayrollProfile;
use App\Models\LeaveLedgerEntry;
use PhpOffice\PhpSpreadsheet\IOFactory;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $leaveExcel = 'C:\\Users\\Lenovo\\Music\\Leave Balance.xlsx';
        $salaryExcel = 'C:\\Users\\Lenovo\\Music\\Salary sheet.xlsx';

        if (file_exists($leaveExcel)) {
            $spreadsheet = IOFactory::load($leaveExcel);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);
            $headers = $rows[1] ?? [];

            // Standardize headers
            $headersMap = [];
            foreach ($headers as $col => $header) {
                if ($header !== null) {
                    $headersMap[strtolower(str_replace([' ', '_', '-', '.', '/'], '', trim($header)))] = $col;
                }
            }

            foreach ($rows as $rowIndex => $row) {
                if ($rowIndex === 1) continue; // Skip header

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
                        'import_source' => 'One-time Repair',
                    ]);

                    $user->leave_balance = $remaining;
                    $user->save();

                    $diff = $remaining - $oldRemaining;
                    if ((float)$diff !== 0.0) {
                        LeaveLedgerEntry::create([
                            'user_id' => $user->id,
                            'amount' => $diff,
                            'type' => 'adjustment',
                            'description' => "Remaining Leave repaired via one-time data correction: {$oldRemaining} -> {$remaining}",
                        ]);
                    }
                }
            }
        }

        if (file_exists($salaryExcel)) {
            $spreadsheet = IOFactory::load($salaryExcel);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);
            $headers = $rows[1] ?? [];

            // Standardize headers
            $headersMap = [];
            foreach ($headers as $col => $header) {
                if ($header !== null) {
                    $headersMap[strtolower(str_replace([' ', '_', '-', '.', '/'], '', trim($header)))] = $col;
                }
            }

            foreach ($rows as $rowIndex => $row) {
                if ($rowIndex === 1) continue; // Skip header

                $empCodeCol = $headersMap['empid'] ?? null;
                if (!$empCodeCol || empty($row[$empCodeCol])) continue;

                $empCode = trim((string)$row[$empCodeCol]);
                $standardId = 'EMP' . str_pad(preg_replace('/[^0-9]/', '', $empCode), 5, '0', STR_PAD_LEFT);

                $user = User::where('employee_id', $standardId)
                    ->orWhere('employee_id', $empCode)
                    ->first();

                if ($user) {
                    $salaryVal = isset($headersMap['empsalary']) ? $row[$headersMap['empsalary']] : null;
                    if ($salaryVal !== null) {
                        $salary = (float)preg_replace('/[^0-9.-]/', '', $salaryVal);
                        $payrollProfile = PayrollProfile::firstOrCreate(['user_id' => $user->id]);
                        
                        $oldSalary = (float)$payrollProfile->base_salary;
                        if ($oldSalary !== $salary) {
                            $payrollProfile->update([
                                'base_salary' => $salary,
                                'salary_effective_date' => $payrollProfile->salary_effective_date ?? '2026-07-01',
                                'payroll_enabled' => true,
                                'last_imported_at' => now(),
                                'import_source' => 'One-time Repair',
                            ]);

                            $payrollProfile->recordSalaryRevision(
                                $salary,
                                $payrollProfile->salary_effective_date ?? '2026-07-01',
                                'Salary repaired via one-time data correction',
                                null,
                                'One-time Repair'
                            );
                        }
                    }
                }
            }
        }

        // Fallback: Safety reconciliation to sync user leave_balance with LeaveBalance remaining_leave
        $balances = LeaveBalance::all();
        foreach ($balances as $lb) {
            $user = $lb->user;
            if ($user && (float)$user->leave_balance !== (float)$lb->remaining_leave) {
                $user->leave_balance = $lb->remaining_leave;
                $user->saveQuietly();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // One-time repairs are not reversible
    }
};
