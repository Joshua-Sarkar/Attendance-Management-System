<?php

use App\Models\User;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\PayrollProfile;
use App\Models\SalaryHistory;
use App\Models\LeaveBalance;
use App\Models\ImportProfile;
use App\Models\EmployeeExternalIdentifier;
use App\Models\ImportLog;
use App\Services\EmployeeImportService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function createTestImportExcel(array $headers, array $data): string
{
    $tempFile = tempnam(storage_path('app'), 'external_import_test') . '.xlsx';
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

test('auto profile detection resolves correct format mappings', function () {
    $service = resolve(EmployeeImportService::class);
    $service->ensureDefaultProfilesExist();

    // Test with Standard headers
    $standardHeaders = ['Employee Code', 'Full Name', 'Official Email ID', 'Base Salary', 'Planned Leave'];
    $profileStd = $service->detectProfile($standardHeaders);
    expect($profileStd->source_system)->toBe('Standard');

    // Test with Zimyo headers
    $zimyoHeaders = ['emp code', 'name', 'email'];
    $profileZm = $service->detectProfile($zimyoHeaders);
    expect($profileZm->source_system)->toBe('Zimyo');
});

test('spreadsheet health reports missing required and duplicate check errors', function () {
    $service = resolve(EmployeeImportService::class);
    $service->ensureDefaultProfilesExist();

    // Headers missing official_email and full_name in create mode
    $headers = ['Employee Code', 'Unrecognized Column 123'];
    $data = [
        ['Employee Code' => 'ZM-100', 'Unrecognized Column 123' => 'Value'],
        ['Employee Code' => 'ZM-100', 'Unrecognized Column 123' => 'Value'], // duplicate external ID
    ];

    $filePath = createTestImportExcel($headers, $data);

    $preview = $service->preview($filePath, 'create', [], 'auto');

    expect($preview['spreadsheet_health']['has_errors'])->toBeTrue();
    expect($preview['spreadsheet_health']['header_validation'])->toBe('MISSING_REQUIRED');
    expect($preview['spreadsheet_health']['missing_required_columns'])->toContain('official_email');
    expect($preview['spreadsheet_health']['unknown_columns'])->toContain('Unrecognized Column 123');
    expect($preview['spreadsheet_health']['duplicate_external_identifiers'])->toHaveCount(1);
    expect($preview['spreadsheet_health']['duplicate_external_identifiers'][0]['external_identifier'])->toBe('ZM-100');

    if (file_exists($filePath)) {
        unlink($filePath);
    }
});

test('multi-stage matching groups suggested email matches and manual name department reviews', function () {
    $dept = Department::create(['name' => 'Human Resources', 'code' => 'HR']);
    $service = resolve(EmployeeImportService::class);
    $service->ensureDefaultProfilesExist();

    // User A: Matching by Official Email
    $userA = User::factory()->create([
        'employee_id' => 'EMP00050',
        'email' => 'user_a@ams.com',
        'name' => 'User A Matches',
        'role' => 'employee',
        'status' => 'active',
        'department_id' => $dept->id,
    ]);

    // User B: Matching by Name + Department (to be manually reviewed)
    $userB = User::factory()->create([
        'employee_id' => 'EMP00051',
        'email' => 'user_b@ams.com',
        'name' => 'User B Matches',
        'role' => 'employee',
        'status' => 'active',
        'department_id' => $dept->id,
    ]);

    $headers = ['Employee Code', 'Full Name', 'Official Email ID', 'Department'];
    $data = [
        [
            'Employee Code' => 'ZM-A50',
            'Full Name' => 'User A Matches',
            'Official Email ID' => 'user_a@ams.com', // matched via email
            'Department' => 'Human Resources',
        ],
        [
            'Employee Code' => 'ZM-B51',
            'Full Name' => 'User B Matches', // matched via name + department
            'Official Email ID' => 'different@ams.com',
            'Department' => 'Human Resources',
        ],
    ];

    $filePath = createTestImportExcel($headers, $data);

    $preview = $service->preview($filePath, 'update', ['base_salary']);

    // Assert Suggested Matches (90% confidence from Email + Name + Department)
    expect($preview['suggested_matches_count'])->toBe(1);
    expect($preview['suggested_employee_matches'][0]['external_code'])->toBe('ZM-A50');
    expect($preview['suggested_employee_matches'][0]['user_id'])->toBe($userA->id);
    expect($preview['suggested_employee_matches'][0]['confidence'])->toBe(90);

    // Assert Needs Manual Review (40% confidence from Name + Department)
    expect($preview['needs_manual_review_count'])->toBe(1);
    expect($preview['needs_manual_review'][0]['external_code'])->toBe('ZM-B51');
    expect($preview['needs_manual_review'][0]['confidence'])->toBe(40);
    expect($preview['needs_manual_review'][0]['candidates'][0]['id'])->toBe($userB->id);

    if (file_exists($filePath)) {
        unlink($filePath);
    }
});

test('approved mappings are written to mappings database table with audit fields on import confirmation', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $user = User::factory()->create([
        'employee_id' => 'EMP00060',
        'email' => 'user60@ams.com',
        'name' => 'User Sixty',
        'status' => 'active',
    ]);

    $headers = ['Employee Code', 'Base Salary', 'Salary Effective Date'];
    $data = [
        [
            'Employee Code' => 'ZM-EX60',
            'Base Salary' => '90000.00',
            'Salary Effective Date' => '2026-07-01',
        ]
    ];
    $filePath = createTestImportExcel($headers, $data);
    $service = resolve(EmployeeImportService::class);

    // Run import confirming manual mapping of ZM-EX60 -> $user->id
    $result = $service->import(
        $filePath,
        'update',
        ['base_salary'],
        $admin->id,
        ['ZM-EX60' => $user->id]
    );

    expect($result['updated'])->toBe(1);

    // Assert mapping database record with audits was stored
    $mapping = EmployeeExternalIdentifier::where('external_identifier', 'ZM-EX60')->first();
    expect($mapping)->not->toBeNull();
    expect($mapping->user_id)->toBe($user->id);
    expect($mapping->verified_by_id)->toBe($admin->id);
    expect($mapping->verified_at)->not->toBeNull();
    expect($mapping->is_active)->toBeTrue();

    if (file_exists($filePath)) {
        unlink($filePath);
    }
});

test('transaction rolls back entire import if atomic database write fails', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $user1 = User::factory()->create([
        'employee_id' => 'EMP00070',
        'email' => 'user70@ams.com',
        'name' => 'User Seventy',
        'status' => 'active',
    ]);

    $user2 = User::factory()->create([
        'employee_id' => 'EMP00071',
        'email' => 'user71@ams.com',
        'name' => 'User Seventy One',
        'status' => 'active',
    ]);

    // Initialize leave balances
    $user1->leaveBalance()->create(['planned_leave' => 5, 'remaining_leave' => 5]);
    $user2->leaveBalance()->create(['planned_leave' => 5, 'remaining_leave' => 5]);

    // Excel sheet:
    // Row 1: Valid salary and leave update
    // Row 2: Will trigger database integrity violation because Base Salary is negative/invalid or employee resolves to empty
    $headers = ['Employee Code', 'Base Salary', 'Planned Leave'];
    $data = [
        [
            'Employee Code' => '70',
            'Base Salary' => '30000',
            'Planned Leave' => '10',
        ],
        [
            'Employee Code' => '71',
            'Base Salary' => '-999', // invalid negative salary
            'Planned Leave' => 'abc', // invalid text value
        ]
    ];
    $filePath = createTestImportExcel($headers, $data);
    $service = resolve(EmployeeImportService::class);

    // Force validation or execution exception by providing incomplete or broken mapping to trigger db constraints or custom exceptions
    // Let's pass a completely non-existent user mapping or database error trigger
    $failed = false;
    try {
        // ZM-EX71 resolves to invalid code or missing mapping under strict mode, throwing exception
        $service->import(
            $filePath,
            'update',
            ['base_salary', 'leave_balances'],
            $admin->id,
            ['70' => $user1->id, '71' => 999999] // 999999 triggers foreign key constraint violation
        );
    } catch (\Throwable $e) {
        $failed = true;
    }

    expect($failed)->toBeTrue();

    // Confirm that User 70 was NOT updated (rolled back completely)
    $user1->refresh();
    expect($user1->payrollProfile?->base_salary)->toBeNull();
    expect((float)$user1->leaveBalance?->planned_leave)->toBe(5.0);

    if (file_exists($filePath)) {
        unlink($filePath);
    }
});
