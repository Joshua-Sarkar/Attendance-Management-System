<?php

use App\Models\User;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\PayrollProfile;
use App\Models\SalaryHistory;
use App\Models\LeaveBalance;
use App\Models\LeaveLedgerEntry;
use App\Models\ImportLog;
use App\Services\EmployeeImportService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function createTestExcel(array $headers, array $data): string
{
    $tempFile = tempnam(storage_path('app'), 'import_test_selective') . '.xlsx';
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Headers
    foreach ($headers as $colIndex => $header) {
        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
        $sheet->setCellValue($colLetter . '1', $header);
    }

    // Rows
    foreach ($data as $rowIndex => $rowData) {
        foreach ($headers as $colIndex => $header) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $value = $rowData[$header] ?? '';
            $sheet->setCellValue($colLetter . ($rowIndex + 2), $value);
        }
    }

    $writer = new Xlsx($spreadsheet);
    $writer->save($tempFile);

    return $tempFile;
}

test('import preview reports correct matched and not found counts', function () {
    // 1. Setup pre-requisites
    $dept = Department::create(['name' => 'Engineering', 'code' => 'ENG']);
    
    // Existing user
    $existing = User::factory()->create([
        'employee_id' => 'EMP00001',
        'email' => 'existing@ams.com',
        'name' => 'Existing Employee',
        'role' => 'employee',
        'status' => 'active',
        'department_id' => $dept->id,
    ]);
    
    // Prepare Excel file: Row 1 matches existing, Row 2 is new employee
    $headers = ['Employee Code', 'Full Name', 'Official Email ID', 'Department', 'Employee Status', 'Base Salary'];
    $data = [
        [
            'Employee Code' => '1',
            'Full Name' => 'Existing Employee',
            'Official Email ID' => 'existing@ams.com',
            'Department' => 'Engineering',
            'Employee Status' => 'Active',
            'Base Salary' => '50000',
        ],
        [
            'Employee Code' => '2',
            'Full Name' => 'New Employee',
            'Official Email ID' => 'new@ams.com',
            'Department' => 'Engineering',
            'Employee Status' => 'Active',
            'Base Salary' => '60000',
        ],
    ];

    $filePath = createTestExcel($headers, $data);
    $service = resolve(EmployeeImportService::class);

    // Assert preview in Create mode
    $previewCreate = $service->preview($filePath, 'create', []);
    expect($previewCreate['matched_count'])->toBe(1); // EMP00001
    expect($previewCreate['updated_count'])->toBe(1); // EMP00002 is expected to be created
    expect($previewCreate['skipped_count'])->toBe(1); // EMP00001 is skipped in create mode
    expect($previewCreate['not_found_count'])->toBe(0);

    // Assert preview in Update mode
    $previewUpdate = $service->preview($filePath, 'update', ['base_salary']);
    expect($previewUpdate['matched_count'])->toBe(1); // EMP00001 matches
    expect($previewUpdate['updated_count'])->toBe(1); // EMP00001 has new salary
    expect($previewUpdate['skipped_count'])->toBe(0); 
    expect($previewUpdate['not_found_count'])->toBe(0); // Employee Verification card resolved instead
    expect($previewUpdate['needs_manual_review_count'])->toBe(1); // EMP00002 requires review in update mode

    if (file_exists($filePath)) {
        unlink($filePath);
    }
});

test('create mode initializes default profiles and imports payroll/leaves if present', function () {
    $dept = Department::create(['name' => 'Support', 'code' => 'SUP']);

    $headers = [
        'Employee Code', 'Full Name', 'Official Email ID', 'Department', 'Employee Status',
        'Base Salary', 'Salary Effective Date', 'Payroll Enabled',
        'Planned Leave', 'Remaining Leave'
    ];
    $data = [
        [
            'Employee Code' => '10',
            'Full Name' => 'Support Agent',
            'Official Email ID' => 'support10@ams.com',
            'Department' => 'Support',
            'Employee Status' => 'Active',
            'Base Salary' => '45000.50',
            'Salary Effective Date' => '2026-07-01',
            'Payroll Enabled' => 'Yes',
            'Planned Leave' => '15.5',
            'Remaining Leave' => '12.0',
        ]
    ];

    $filePath = createTestExcel($headers, $data);
    $service = resolve(EmployeeImportService::class);

    $result = $service->import($filePath, 'create');
    expect($result['created'])->toBe(1);
    expect($result['skipped'])->toBe(0);

    $user = User::where('employee_id', 'EMP00010')->first();
    expect($user)->not->toBeNull();

    // Verify Payroll Profile
    $payroll = $user->payrollProfile;
    expect($payroll)->not->toBeNull();
    expect((float)$payroll->base_salary)->toBe(45000.50);
    expect($payroll->salary_effective_date->format('Y-m-d'))->toBe('2026-07-01');
    expect($payroll->payroll_enabled)->toBeTrue();

    // Verify Salary History record
    $history = SalaryHistory::where('payroll_profile_id', $payroll->id)->first();
    expect($history)->not->toBeNull();
    expect((float)$history->base_salary)->toBe(45000.50);

    // Verify Leave Balance
    $leaves = $user->leaveBalance;
    expect($leaves)->not->toBeNull();
    expect((float)$leaves->planned_leave)->toBe(15.5);
    expect((float)$leaves->remaining_leave)->toBe(12.0);
    expect((float)$leaves->total_leave)->toBe(15.5); // planning only planned + carry forward (which is 0)

    // Verify user->leave_balance cache was synced
    expect((float)$user->leave_balance)->toBe(12.0);

    // Verify Ledger audit entry was logged
    $ledger = LeaveLedgerEntry::where('user_id', $user->id)->where('type', 'adjustment')->first();
    expect($ledger)->not->toBeNull();
    // 12.0 remaining - 2.0 default initialized = +10.0 adjustment
    expect((float)$ledger->amount)->toBe(10.0);

    if (file_exists($filePath)) {
        unlink($filePath);
    }
});

test('update mode selectively updates base salary and leave balances', function () {
    $dept = Department::create(['name' => 'Engineering', 'code' => 'ENG']);

    $user = User::factory()->create([
        'employee_id' => 'EMP00020',
        'email' => 'emp20@ams.com',
        'name' => 'Employee Twenty',
        'role' => 'employee',
        'status' => 'active',
        'department_id' => $dept->id,
        'leave_balance' => 5.0,
    ]);

    // Initialize profiles
    $user->payrollProfile()->create([
        'base_salary' => 30000.0,
        'salary_effective_date' => '2026-01-01',
        'payroll_enabled' => false,
    ]);
    $user->leaveBalance()->create([
        'planned_leave' => 5.0,
        'remaining_leave' => 5.0,
    ]);

    $headers = [
        'Employee Code', 'Full Name', 'Official Email ID', 'Department', 'Employee Status',
        'Base Salary', 'Salary Effective Date', 'Payroll Enabled',
        'Planned Leave', 'Remaining Leave'
    ];
    $data = [
        [
            'Employee Code' => '20',
            'Full Name' => 'New Unrelated Name', // should be ignored
            'Official Email ID' => 'unrelated@ams.com', // should be ignored
            'Department' => 'Support', // should be ignored
            'Employee Status' => 'Inactive', // should be ignored
            'Base Salary' => '35000.00',
            'Salary Effective Date' => '2026-07-01',
            'Payroll Enabled' => 'Yes',
            'Planned Leave' => '8.0',
            'Remaining Leave' => '7.5',
        ]
    ];

    $filePath = createTestExcel($headers, $data);
    $service = resolve(EmployeeImportService::class);
    $service->matchingService->setAutoMatchThreshold(60.0);

    // Update only Base Salary category
    $result = $service->import($filePath, 'update', ['base_salary']);
    expect($result['updated'])->toBe(1);
    expect($result['skipped'])->toBe(0);

    $user->refresh();
    // User core properties must remain unchanged
    expect($user->name)->toBe('Employee Twenty');
    expect($user->email)->toBe('emp20@ams.com');
    expect($user->status)->toBe('active');
    expect($user->department_id)->toBe($dept->id);

    // Base salary must be updated
    expect((float)$user->payrollProfile->base_salary)->toBe(35000.00);
    expect($user->payrollProfile->salary_effective_date->format('Y-m-d'))->toBe('2026-07-01');
    expect($user->payrollProfile->payroll_enabled)->toBeTrue();

    // Leave balances must remain UNCHANGED (since leave_balances category was not selected)
    expect((float)$user->leaveBalance->planned_leave)->toBe(5.0);
    expect((float)$user->leaveBalance->remaining_leave)->toBe(5.0);
    expect((float)$user->leave_balance)->toBe(5.0);

    // Now update only Leave Balances category
    $result2 = $service->import($filePath, 'update', ['leave_balances']);
    expect($result2['updated'])->toBe(1);

    $user->refresh();
    // Leaves must now be updated
    expect((float)$user->leaveBalance->planned_leave)->toBe(8.0);
    expect((float)$user->leaveBalance->remaining_leave)->toBe(7.5);
    // User leave balance cache must be updated
    expect((float)$user->leave_balance)->toBe(7.5);

    // Double entry ledger adjustment logged
    $ledger = LeaveLedgerEntry::where('user_id', $user->id)
        ->where('description', 'like', '%Remaining Leave adjusted%')
        ->first();
    expect($ledger)->not->toBeNull();
    // 7.5 remaining - 5.0 old remaining = 2.5 adjustment
    expect((float)$ledger->amount)->toBe(2.5);

    if (file_exists($filePath)) {
        unlink($filePath);
    }
});

test('blank cells and missing columns do not overwrite existing database values', function () {
    $dept = Department::create(['name' => 'Engineering', 'code' => 'ENG']);

    $user = User::factory()->create([
        'employee_id' => 'EMP00030',
        'email' => 'emp30@ams.com',
        'name' => 'Employee Thirty',
        'role' => 'employee',
        'status' => 'active',
        'department_id' => $dept->id,
    ]);

    // Initialize with values
    $user->payrollProfile()->create([
        'base_salary' => 80000.00,
        'salary_effective_date' => '2026-01-01',
        'payroll_enabled' => true,
    ]);
    $user->leaveBalance()->create([
        'planned_leave' => 10.0,
        'remaining_leave' => 8.0,
        'unplanned_leave' => 5.0,
    ]);

    // Excel sheet:
    // - Base Salary column is blank
    // - Planned Leave is blank
    // - Remaining Leave is blank
    // - Unplanned Leave column is completely missing
    $headers = [
        'Employee Code', 'Base Salary', 'Salary Effective Date', 'Planned Leave', 'Remaining Leave'
    ];
    $data = [
        [
            'Employee Code' => '30',
            'Base Salary' => '', // blank
            'Salary Effective Date' => '2026-07-01', // filled
            'Planned Leave' => '  ', // blank whitespace
            'Remaining Leave' => '', // blank
        ]
    ];

    $filePath = createTestExcel($headers, $data);
    $service = resolve(EmployeeImportService::class);
    $service->matchingService->setAutoMatchThreshold(60.0);

    $result = $service->import($filePath, 'update', ['base_salary', 'leave_balances']);
    expect($result['updated'])->toBe(1);

    $user->refresh();
    // Base salary should remain unchanged (since cell was blank)
    expect((float)$user->payrollProfile->base_salary)->toBe(80000.00);
    // Salary effective date should be updated (since cell was filled)
    expect($user->payrollProfile->salary_effective_date->format('Y-m-d'))->toBe('2026-07-01');

    // Planned leave and remaining leave should remain unchanged (since cells were blank)
    expect((float)$user->leaveBalance->planned_leave)->toBe(10.0);
    expect((float)$user->leaveBalance->remaining_leave)->toBe(8.0);
    // Unplanned leave should remain unchanged (since column was missing)
    expect((float)$user->leaveBalance->unplanned_leave)->toBe(5.0);

    if (file_exists($filePath)) {
        unlink($filePath);
    }
});

test('admin can upload file and execute two-step confirm process', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
        'must_change_password' => false,
    ]);

    $dept = Department::create(['name' => 'Operations', 'code' => 'OPS']);

    $user = User::factory()->create([
        'employee_id' => 'EMP00040',
        'email' => 'emp40@ams.com',
        'name' => 'Employee Forty',
        'status' => 'active',
        'department_id' => $dept->id,
    ]);

    $user->payrollProfile()->create([
        'base_salary' => 20000.0,
        'salary_effective_date' => '2026-01-01',
    ]);

    // Create temporary Excel file
    $headers = ['Employee Code', 'Full Name', 'Official Email ID', 'Base Salary', 'Salary Effective Date'];
    $data = [
        [
            'Employee Code' => '40',
            'Full Name' => 'Employee Forty',
            'Official Email ID' => 'emp40@ams.com',
            'Base Salary' => '28000.00',
            'Salary Effective Date' => '2026-07-01',
        ]
    ];
    $filePath = createTestExcel($headers, $data);

    // Create uploaded file instance
    $uploadedFile = new \Illuminate\Http\UploadedFile(
        $filePath,
        'employees_selective.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true
    );

    // 1. Submit handleUpload to trigger preview
    $response = $this->actingAs($admin)
        ->post(route('admin.import.handle'), [
            'file' => $uploadedFile,
            'mode' => 'update',
            'update_categories' => ['base_salary'],
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('import_preview');

    $preview = session('import_preview');
    expect($preview['matched_count'])->toBe(1);
    expect($preview['updated_count'])->toBe(1);
    expect($preview['mode'])->toBe('update');

    // 2. Submit confirmImport
    $confirmResponse = $this->actingAs($admin)
        ->post(route('admin.import.confirm'), [
            'temp_file_path' => $preview['temp_file_path'],
            'mode' => 'update',
            'update_categories' => ['base_salary'],
            'original_filename' => 'employees_selective.xlsx',
        ]);

    $confirmResponse->assertRedirect(route('admin.import.show'));
    $confirmResponse->assertSessionHas('success');
    $confirmResponse->assertSessionHas('import_results');

    // Verify database updates
    $user->refresh();
    expect((float)$user->payrollProfile->base_salary)->toBe(28000.00);

    // Verify ImportLog record with skipping & duration
    $log = ImportLog::where('filename', 'employees_selective.xlsx')->first();
    expect($log)->not->toBeNull();
    expect($log->updated_count)->toBe(1);
    expect($log->skipped_count)->toBe(0);
    expect($log->duration_seconds)->not->toBeNull();
    expect($log->metadata['mode'])->toBe('update');
    expect($log->metadata['update_categories'])->toContain('base_salary');

    if (file_exists($filePath)) {
        @unlink($filePath);
    }
});

test('two-way synchronization of remaining leave and user leave balance', function () {
    $user = User::factory()->create([
        'leave_balance' => 10.00,
    ]);
    
    $leaveBalance = $user->leaveBalance()->create([
        'planned_leave' => 5.00,
        'remaining_leave' => 10.00,
    ]);

    // 1. Update user.leave_balance -> should sync to leaveBalance.remaining_leave
    $user->leave_balance = 8.50;
    $user->save();

    $leaveBalance->refresh();
    expect((float)$leaveBalance->remaining_leave)->toBe(8.50);

    // 2. Update leaveBalance.remaining_leave -> should sync to user.leave_balance
    $leaveBalance->remaining_leave = 15.00;
    $leaveBalance->save();

    $user->refresh();
    expect((float)$user->leave_balance)->toBe(15.00);
});

test('import supports total remaining and total carry forward headers', function () {
    $dept = Department::create(['name' => 'Finance', 'code' => 'FIN']);
    
    $headers = [
        'Employee Code', 'Full Name', 'Official Email ID', 'Department', 'Employee Status',
        'Total Carry Forward', 'Total Remaining'
    ];
    $data = [
        [
            'Employee Code' => '100',
            'Full Name' => 'Finance Analyst',
            'Official Email ID' => 'fin100@ams.com',
            'Department' => 'Finance',
            'Employee Status' => 'Active',
            'Total Carry Forward' => '12.0',
            'Total Remaining' => '15.0',
        ]
    ];

    $filePath = createTestExcel($headers, $data);
    $service = resolve(EmployeeImportService::class);
    
    // Auto-refresh default profiles to make sure new mappings are loaded
    $service->ensureDefaultProfilesExist();

    $result = $service->import($filePath, 'create');
    expect($result['created'])->toBe(1);

    $user = User::where('employee_id', 'EMP00100')->first();
    expect($user)->not->toBeNull();

    $leaves = $user->leaveBalance;
    expect($leaves)->not->toBeNull();
    expect((float)$leaves->carry_forward)->toBe(12.0);
    expect((float)$leaves->remaining_leave)->toBe(15.0);
    expect((float)$user->leave_balance)->toBe(15.0);

    if (file_exists($filePath)) {
        @unlink($filePath);
    }
});
