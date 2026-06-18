<?php

use App\Models\User;
use App\Models\EmployeeProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

test('employee profile can be created and has bidirectional relationships', function () {
    $user = User::factory()->create();

    $profileData = [
        'user_id' => $user->id,
        // Personal
        'father_name' => 'John Doe Sr.',
        'mother_name' => 'Jane Doe Sr.',
        'gender' => 'Male',
        'date_of_birth' => '1990-01-01',
        'marital_status' => 'Married',
        'date_of_marriage' => '2015-05-05',
        'nationality' => 'Indian',
        'blood_group' => 'O+',
        'personal_email' => 'personal@example.com',
        'mobile_no' => '9876543210',
        
        // Government IDs
        'pf_uan' => 'UAN123456',
        'passport_no' => 'PASS9876',
        'aadhar_card' => '1234-5678-9012',
        'pan' => 'ABCDE1234F',
        'pf_no' => 'PF776655',
        'esi_number' => 'ESI112233',
        'date_of_gratuity' => '2020-06-01',

        // Employment
        'payroll_type' => 'Monthly',
        'contract_end_date' => '2028-12-31',
        'office_landline' => '022-12345678',
        'leave_rule' => 'Standard Rule',
        'shift' => 'Morning',
        'designation' => 'Software Engineer',
        'grade' => 'A3',
        'employee_type' => 'Full-time',
        'company' => 'Awesome Corp',
        'location' => 'Mumbai',
        'biometric_id' => 'BIO007',
        'hiring_source' => 'Referral',
        'source_of_verification' => 'Background Check Corp',

        // Current Address
        'current_address1' => 'Flat 101, building A',
        'current_address2' => 'Main Road, Area B',
        'current_country' => 'India',
        'current_state' => 'Maharashtra',
        'current_city' => 'Mumbai',
        'current_zip' => '400001',

        // Permanent Address
        'permanent_address1' => 'Flat 101, building A',
        'permanent_address2' => 'Main Road, Area B',
        'permanent_country' => 'India',
        'permanent_state' => 'Maharashtra',
        'permanent_city' => 'Mumbai',
        'permanent_zip' => '400001',
        'same_as_current_address' => true,

        // Bank
        'payment_type' => 'Bank Transfer',
        'bank_name' => 'HDFC Bank',
        'account_holder_name' => 'John Doe',
        'account_no' => '987654321098',
        'ifsc_code' => 'HDFC0000123',

        // Emergency Contact
        'emergency_name' => 'Jane Doe',
        'emergency_relationship' => 'Spouse',
        'emergency_address' => 'Flat 101, building A, Mumbai',
        'emergency_email' => 'spouse@example.com',
        'emergency_mobile' => '9876543211',

        // Education
        'degree_name' => 'B.Tech Computer Science',
        'institution_name' => 'IIT Bombay',
        'passing_year' => '2012',
        'percentage' => '85%',

        // Previous Employment
        'previous_company_name' => 'Old Tech Inc',
        'previous_job_title' => 'Junior Developer',
        'previous_from_date' => '2012-06-01',
        'previous_to_date' => '2015-05-01',

        // Tenure
        'probation_period' => '6 Months',
        'probation_confirm_date' => '2015-11-01',
        'separation_date' => null,
        'last_working_day' => null,
        'previous_year_experience' => 3.00,
        'years_completed' => 5.50,
        'overall_year_experience' => 8.50,

        // Additional fields
        'city_type' => 'Metro',
        'notice_days' => 60,
        'state_name' => 'Maharashtra',
        'joining_date' => '2026-06-18',
    ];

    $profile = EmployeeProfile::create($profileData);

    // Verify model can retrieve all fields and relationship works
    expect($profile->father_name)->toBe('John Doe Sr.');
    expect($profile->user)->toBeInstanceOf(User::class);
    expect($profile->user->id)->toBe($user->id);

    // Verify User hasOne relationship
    $user->refresh();
    expect($user->employeeProfile)->toBeInstanceOf(EmployeeProfile::class);
    expect($user->employeeProfile->father_name)->toBe('John Doe Sr.');

    // Verify dates and booleans are casted correctly
    expect($profile->date_of_birth)->toBeInstanceOf(\Carbon\Carbon::class);
    expect($profile->date_of_birth->format('Y-m-d'))->toBe('1990-01-01');
    expect($profile->same_as_current_address)->toBeTrue();

    // Verify additional fields are stored and casted correctly
    expect($profile->city_type)->toBe('Metro');
    expect($profile->notice_days)->toBe(60);
    expect($profile->state_name)->toBe('Maharashtra');
    expect($profile->joining_date)->toBeInstanceOf(\Carbon\Carbon::class);
    expect($profile->joining_date->format('Y-m-d'))->toBe('2026-06-18');
});

test('specified fields are encrypted in the database but decrypted when accessed via model', function () {
    $user = User::factory()->create();

    $profile = EmployeeProfile::create([
        'user_id' => $user->id,
        'aadhar_card' => '1234-5678-9012',
        'pan' => 'ABCDE1234F',
        'account_no' => '987654321098',
        'ifsc_code' => 'HDFC0000123',
    ]);

    // Retrieve via model -> should be decrypted
    expect($profile->aadhar_card)->toBe('1234-5678-9012');
    expect($profile->pan)->toBe('ABCDE1234F');
    expect($profile->account_no)->toBe('987654321098');
    expect($profile->ifsc_code)->toBe('HDFC0000123');

    // Retrieve raw database record -> should be encrypted and different
    $rawRecord = DB::table('employee_profiles')->where('id', $profile->id)->first();
    
    expect($rawRecord->aadhar_card)->not->toBe('1234-5678-9012');
    expect($rawRecord->pan)->not->toBe('ABCDE1234F');
    expect($rawRecord->account_no)->not->toBe('987654321098');
    expect($rawRecord->ifsc_code)->not->toBe('HDFC0000123');

    // It should be possible to decrypt the raw database values manually using Laravel's Crypt decrypter
    expect(Crypt::decryptString($rawRecord->aadhar_card))->toBe('1234-5678-9012');
    expect(Crypt::decryptString($rawRecord->pan))->toBe('ABCDE1234F');
    expect(Crypt::decryptString($rawRecord->account_no))->toBe('987654321098');
    expect(Crypt::decryptString($rawRecord->ifsc_code))->toBe('HDFC0000123');
});

test('employee profile is deleted when user is deleted (cascade delete)', function () {
    $user = User::factory()->create();
    $profile = EmployeeProfile::create([
        'user_id' => $user->id,
        'father_name' => 'John Doe Sr.',
    ]);

    expect(EmployeeProfile::where('id', $profile->id)->exists())->toBeTrue();

    // Delete user
    $user->delete();

    // Check profile is deleted automatically due to database cascade
    expect(EmployeeProfile::where('id', $profile->id)->exists())->toBeFalse();
});
