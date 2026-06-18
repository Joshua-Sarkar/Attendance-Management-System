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
    $tempFile = tempnam(sys_get_temp_dir(), 'import_test') . '.xlsx';

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
            'PREVIOUS YEAR_EXPERIENCE' => '3.0',
            'NUMBER OF_YEAR_COMPLETED' => '5.0',
            'OVERALL YEAR_EXPERIENCE' => '8.0',
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
    expect($user1->role)->toBe('employee');
    expect($user1->must_change_password)->toBeTrue();
    expect($user1->department_id)->toBe($engineering->id);
    expect($user1->phone)->toBe('1111111111');

    $profile1 = $user1->employeeProfile;
    expect($profile1)->not->toBeNull();
    expect($profile1->father_name)->toBe('Father One');
    expect($profile1->city_type)->toBe('Metro');
    expect($profile1->notice_days)->toBe(30);

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
