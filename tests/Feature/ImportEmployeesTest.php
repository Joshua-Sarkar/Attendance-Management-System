<?php

use App\Models\User;
use App\Models\Department;
use App\Models\EmployeeProfile;
use Illuminate\Support\Facades\Artisan;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

test('it imports employees and updates profiles correctly from xlsx', function () {
    // 1. Setup Pre-requisites
    // Create Engineering department
    $engineering = Department::create([
        'name' => 'Engineering',
        'code' => 'ENG',
        'description' => 'Engineering Department',
    ]);

    // Create User Three who will be updated
    $existingUser = User::factory()->create([
        'employee_id' => 'EMP00003',
        'email' => 'user3@example.com',
        'name' => 'Original User Three',
        'role' => 'employee',
        'status' => 'inactive',
        'department_id' => null,
    ]);

    // Create a temporary Excel file
    $tempFile = tempnam(storage_path('app'), 'import_test') . '.xlsx';

    $headers = [
        'Employee Code', 'City Type', 'Full Name', 'Father Name', 'Mother Name', 'Profile picture', 'Gender', 
        'Date of birth', 'Marital Status', 'Date of Marriage', 'Nationality', 'PF UAN', 'Joining Date', 
        'Blood Group', 'Notice Days', 'Mobile No.', 'Personal Email ID', 'Passport No.', 'Aadhar Card', 'PAN', 
        'PF NO', 'Date of Gratuity', 'Esi number', 'Payroll Type', 'Contract End Date', 
        'Office Landline Number', 'Leave Rule', 'Reporting Manager', 'Shift', 'Department', 'Designation', 
        'Grade', 'Employee Type', 'Company', 'Location', 'Official Email ID', 'Biometric Id', 'Hiring source', 
        'Employee Status', 'Source of verification', 'Current Address1', 'Current Address2', 'Country', 
        'State', 'City', 'Zip', 'Same as current address', 'Permanent Address1', 'Permanent Address2', 
        'Country.1', 'State.1', 'City.1', 'Zip.1', 'Payment Type', 'Bank Name', 'Account No', 'IFSC code', 
        'Account Holder Name', 'Name', 'Relationship', 'Address', 'Email', 'Mobile No..1', 
        'Diploma/Degree Name', 'Institution Name', 'Passing Year', 'Percentage', 'Previous Company Name', 
        'Job Title', 'From Date', 'To Date', 'STATE NAME', 'PROBATION PERIOD', 'PROBATION CONFIRM_DATE', 
        'SEPRATE DATE', 'LWD', 'PREVIOUS YEAR_EXPERIENCE', 'NUMBER OF_YEAR_COMPLETED', 
        'OVERALL YEAR_EXPERIENCE', 'EMPLOYEE NAME'
    ];

    $data = [
        // Row 2: Create normal user
        [
            'Employee Code' => '1',
            'City Type' => 'Metro',
            'Full Name' => 'User One',
            'Father Name' => 'Father One',
            'Mother Name' => 'Mother One',
            'Gender' => 'Male',
            'Date of birth' => '1990-01-01',
            'Marital Status' => 'Single',
            'Nationality' => 'Indian',
            'PF UAN' => 'UAN1',
            'Joining Date' => '2026-06-18',
            'Blood Group' => 'A+',
            'Notice Days' => '30',
            'Mobile No.' => '1111111111',
            'Personal Email ID' => 'personal1@example.com',
            'Passport No.' => 'PASS1',
            'Aadhar Card' => 'AADHAR1',
            'PAN' => 'PAN1',
            'PF NO' => 'PF1',
            'Date of Gratuity' => '2020-01-01',
            'Esi number' => 'ESI1',
            'Payroll Type' => 'Monthly',
            'Office Landline Number' => '022-1111',
            'Leave Rule' => 'Rule 1',
            'Reporting Manager' => '',
            'Shift' => 'Day',
            'Department' => 'Engineering',
            'Designation' => 'Developer',
            'Grade' => 'Grade A',
            'Employee Type' => 'Full-time',
            'Company' => 'Company A',
            'Location' => 'Location A',
            'Official Email ID' => 'user1@example.com',
            'Biometric Id' => 'BIO1',
            'Hiring source' => 'Hiring A',
            'Employee Status' => 'Active',
            'Source of verification' => 'Verification A',
            'Current Address1' => 'Addr1',
            'Current Address2' => 'Addr2',
            'Country' => 'India',
            'State' => 'State A',
            'City' => 'City A',
            'Zip' => '400001',
            'Same as current address' => 'Yes',
            'Permanent Address1' => 'Addr1',
            'Permanent Address2' => 'Addr2',
            'Country.1' => 'India',
            'State.1' => 'State A',
            'City.1' => 'City A',
            'Zip.1' => '400001',
            'Payment Type' => 'Bank',
            'Bank Name' => 'Bank A',
            'Account No' => 'ACC1',
            'IFSC code' => 'IFSC1',
            'Account Holder Name' => 'User One',
            'Name' => 'Emerg Name 1',
            'Relationship' => 'Brother',
            'Address' => 'Emerg Addr 1',
            'Email' => 'emerg1@example.com',
            'Mobile No..1' => '9999999991',
            'Diploma/Degree Name' => 'B.Sc',
            'Institution Name' => 'Inst 1',
            'Passing Year' => '2012',
            'Percentage' => '80%',
            'Previous Company Name' => 'Prev A',
            'Job Title' => 'Dev',
            'From Date' => '2012-01-01',
            'To Date' => '2015-01-01',
            'STATE NAME' => 'State A',
            'PROBATION PERIOD' => '6 months',
            'PROBATION CONFIRM_DATE' => '2015-07-01',
            'SEPRATE DATE' => '',
            'LWD' => '',
            'PREVIOUS YEAR_EXPERIENCE' => '6 Year, 7 Month, 12 Days',
            'NUMBER OF_YEAR_COMPLETED' => '5 Year, 3 Month',
            'OVERALL YEAR_EXPERIENCE' => '11 Year, 10 Month, 12 Days',
        ],
        // Row 3: Create with Reporting Manager
        [
            'Employee Code' => '2',
            'Full Name' => 'User Two',
            'Official Email ID' => 'user2@example.com',
            'Department' => 'Engineering',
            'Employee Status' => 'Probation',
            'Reporting Manager' => 'User One (00000001)',
        ],
        // Row 4: Update Existing User
        [
            'Employee Code' => '3',
            'Full Name' => 'Modified Name In Sheet', // updates are ONLY profile, status, department, not user.name
            'Official Email ID' => 'user3@example.com',
            'Department' => 'Engineering',
            'Employee Status' => 'Active',
            'Reporting Manager' => 'User One (00000001)',
            'Father Name' => 'Father Three New',
        ],
        // Row 5: Bad Department (should be skipped and logged as error)
        [
            'Employee Code' => '4',
            'Full Name' => 'User Four',
            'Official Email ID' => 'user4@example.com',
            'Department' => 'Non Existent Department',
            'Employee Status' => 'Active',
            'Reporting Manager' => '',
        ],
    ];

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

    // 2. Execute Command
    $exitCode = Artisan::call('employees:import', ['file' => $tempFile]);
    expect($exitCode)->toBe(0);

    // 3. Assertions
    // User One should be created successfully
    $user1 = User::where('employee_id', 'EMP00001')->first();
    expect($user1)->not->toBeNull();
    expect($user1->email)->toBe('user1@example.com');
    expect($user1->name)->toBe('User One');
    expect($user1->status)->toBe('active');
    expect($user1->role)->toBe('manager');
    expect($user1->must_change_password)->toBeTrue();
    expect($user1->department_id)->toBe($engineering->id);
    expect($user1->phone)->toBe('1111111111');

    $profile1 = $user1->employeeProfile;
    expect($profile1)->not->toBeNull();
    expect($profile1->father_name)->toBe('Father One');
    expect($profile1->city_type)->toBe('Metro');
    expect($profile1->notice_days)->toBe(30);
    expect($profile1->previous_year_experience)->toBe('6 Year, 7 Month, 12 Days');
    expect($profile1->years_completed)->toBe('5 Year, 3 Month');
    expect($profile1->overall_year_experience)->toBe('11 Year, 10 Month, 12 Days');

    // Verify birthday credit was initialized correctly
    $birthdayCredit = \App\Models\LeaveCredit::where('user_id', $user1->id)->where('source_identifier', 'birthday_2026')->first();
    expect($birthdayCredit)->not->toBeNull();
    expect($birthdayCredit->status)->toBe('active');

    // User Two should be created and linked to User One as manager
    $user2 = User::where('employee_id', 'EMP00002')->first();
    expect($user2)->not->toBeNull();
    expect($user2->email)->toBe('user2@example.com');
    expect($user2->manager_id)->toBe($user1->id);
    expect($user2->status)->toBe('active'); // probation -> active

    // User Three should be updated: ONLY status, department_id, and profile fields
    $user3 = User::where('employee_id', 'EMP00003')->first();
    expect($user3)->not->toBeNull();
    expect($user3->name)->toBe('Original User Three'); // Name should NOT be updated
    expect($user3->status)->toBe('active'); // inactive -> active
    expect($user3->department_id)->toBe($engineering->id);
    expect($user3->manager_id)->toBe($user1->id);

    $profile3 = $user3->employeeProfile;
    expect($profile3)->not->toBeNull();
    expect($profile3->father_name)->toBe('Father Three New');

    // User Four (bad department) should NOT be created
    $user4 = User::where('employee_id', 'EMP00004')->first();
    expect($user4)->toBeNull();

    // Clean up
    if (file_exists($tempFile)) {
        unlink($tempFile);
    }
});

test('admin can access import page and upload file successfully', function () {
    // 1. Setup pre-requisites
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
    ]);

    $engineering = Department::create([
        'name' => 'Engineering',
        'code' => 'ENG',
        'description' => 'Engineering Department',
    ]);

    // Create temporary Excel file
    $tempFile = tempnam(storage_path('app'), 'import_test_web') . '.xlsx';
    
    $headers = [
        'Employee Code', 'Full Name', 'Official Email ID', 'Department', 'Employee Status'
    ];
    $data = [
        [
            'Employee Code' => '100',
            'Full Name' => 'Web User One',
            'Official Email ID' => 'webuser1@example.com',
            'Department' => 'Engineering',
            'Employee Status' => 'Active',
        ]
    ];

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    foreach ($headers as $colIndex => $header) {
        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
        $sheet->setCellValue($colLetter . '1', $header);
    }
    foreach ($data as $rowIndex => $rowData) {
        foreach ($headers as $colIndex => $header) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . ($rowIndex + 2), $rowData[$header] ?? '');
        }
    }
    $writer = new Xlsx($spreadsheet);
    $writer->save($tempFile);

    // Create an UploadedFile instance
    $uploadedFile = new \Illuminate\Http\UploadedFile(
        $tempFile,
        'employees.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true
    );

    // 2. Access page as Admin
    $response = $this->actingAs($admin)
        ->get(route('admin.import.show'));
    $response->assertStatus(200);

    // 3. Upload file as Admin to trigger preview
    $uploadResponse = $this->actingAs($admin)
        ->post(route('admin.import.handle'), [
            'file' => $uploadedFile,
            'mode' => 'create',
        ]);
    
    $uploadResponse->assertRedirect();
    $uploadResponse->assertSessionHas('import_preview');

    $preview = session('import_preview');

    // 3b. Confirm import as Admin
    $confirmResponse = $this->actingAs($admin)
        ->post(route('admin.import.confirm'), [
            'temp_file_path' => $preview['temp_file_path'],
            'mode' => 'create',
            'original_filename' => 'employees.xlsx',
        ]);

    $confirmResponse->assertRedirect(route('admin.import.show'));
    $confirmResponse->assertSessionHas('success');

    // 4. Assert user was created
    $user = User::where('employee_id', 'EMP00100')->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('Web User One');
    expect($user->email)->toBe('webuser1@example.com');

    // 5. Assert ImportLog was created
    $log = \App\Models\ImportLog::where('filename', 'employees.xlsx')->first();
    expect($log)->not->toBeNull();
    expect($log->run_by_user_id)->toBe($admin->id);
    expect($log->rows_processed)->toBe(1);
    expect($log->created_count)->toBe(1);
    expect($log->error_count)->toBe(0);

    // Clean up
    if (file_exists($tempFile)) {
        @unlink($tempFile);
    }
});

test('non-admin users cannot access the import page or upload files', function () {
    $employee = User::factory()->create([
        'role' => 'employee',
        'status' => 'active',
    ]);

    // 1. Try to access page as employee
    $response = $this->actingAs($employee)
        ->get(route('admin.import.show'));
    $response->assertStatus(403);

    // 2. Try to upload file as employee
    $uploadResponse = $this->actingAs($employee)
        ->post(route('admin.import.handle'), [
            'file' => 'dummy-data',
        ]);
    $uploadResponse->assertStatus(403);
});

test('it rejects import if circular reporting hierarchy is detected', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
        'must_change_password' => false,
    ]);

    Department::create([
        'name' => 'Engineering',
        'code' => 'ENG',
        'description' => 'Engineering Department',
    ]);

    // Create a temporary Excel file with A -> B -> C -> A cycle
    $tempFile = tempnam(storage_path('app'), 'import_test') . '.xlsx';

    $headers = [
        'Employee Code', 'Full Name', 'Official Email ID', 'Reporting Manager', 'Employee Status', 'Department'
    ];

    $data = [
        [
            'Employee Code' => '101',
            'Full Name' => 'User A',
            'Official Email ID' => 'usera@example.com',
            'Reporting Manager' => 'User B (102)',
            'Employee Status' => 'Active',
            'Department' => 'Engineering',
        ],
        [
            'Employee Code' => '102',
            'Full Name' => 'User B',
            'Official Email ID' => 'userb@example.com',
            'Reporting Manager' => 'User C (103)',
            'Employee Status' => 'Active',
            'Department' => 'Engineering',
        ],
        [
            'Employee Code' => '103',
            'Full Name' => 'User C',
            'Official Email ID' => 'userc@example.com',
            'Reporting Manager' => 'User A (101)',
            'Employee Status' => 'Active',
            'Department' => 'Engineering',
        ],
    ];

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Write Headers
    foreach ($headers as $colIndex => $header) {
        $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
        $sheet->setCellValue($colLetter . '1', $header);
    }

    // Write Data
    foreach ($data as $rowIndex => $rowData) {
        foreach ($headers as $colIndex => $header) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $value = $rowData[$header] ?? '';
            $sheet->setCellValue($colLetter . ($rowIndex + 2), $value);
        }
    }

    $writer = new Xlsx($spreadsheet);
    $writer->save($tempFile);

    // Call import
    $importService = resolve(\App\Services\EmployeeImportService::class);
    
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Circular reporting loop detected');
    
    try {
        $importService->import($tempFile);
    } finally {
        if (file_exists($tempFile)) {
            @unlink($tempFile);
        }
    }
});
