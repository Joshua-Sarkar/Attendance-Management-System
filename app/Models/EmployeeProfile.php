<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeProfile extends Model
{
    protected $fillable = [
        'user_id',
        'father_name',
        'mother_name',
        'gender',
        'date_of_birth',
        'marital_status',
        'date_of_marriage',
        'nationality',
        'blood_group',
        'personal_email',
        'mobile_no',
        'pf_uan',
        'passport_no',
        'aadhar_card',
        'pan',
        'pf_no',
        'esi_number',
        'date_of_gratuity',
        'payroll_type',
        'contract_end_date',
        'office_landline',
        'leave_rule',
        'shift',
        'designation',
        'grade',
        'employee_type',
        'company',
        'location',
        'biometric_id',
        'hiring_source',
        'source_of_verification',
        'current_address1',
        'current_address2',
        'current_country',
        'current_state',
        'current_city',
        'current_zip',
        'permanent_address1',
        'permanent_address2',
        'permanent_country',
        'permanent_state',
        'permanent_city',
        'permanent_zip',
        'same_as_current_address',
        'payment_type',
        'bank_name',
        'account_holder_name',
        'account_no',
        'ifsc_code',
        'emergency_name',
        'emergency_relationship',
        'emergency_address',
        'emergency_email',
        'emergency_mobile',
        'degree_name',
        'institution_name',
        'passing_year',
        'percentage',
        'previous_company_name',
        'previous_job_title',
        'previous_from_date',
        'previous_to_date',
        'probation_period',
        'probation_confirm_date',
        'separation_date',
        'last_working_day',
        'previous_year_experience',
        'years_completed',
        'overall_year_experience',
        'city_type',
        'notice_days',
        'state_name',
        'joining_date',
        'employee_category',
    ];

    protected function casts(): array
    {
        return [
            'aadhar_card' => 'encrypted',
            'pan' => 'encrypted',
            'account_no' => 'encrypted',
            'ifsc_code' => 'encrypted',
            'same_as_current_address' => 'boolean',
            'date_of_birth' => 'date',
            'date_of_marriage' => 'date',
            'date_of_gratuity' => 'date',
            'contract_end_date' => 'date',
            'previous_from_date' => 'date',
            'previous_to_date' => 'date',
            'probation_confirm_date' => 'date',
            'separation_date' => 'date',
            'last_working_day' => 'date',
            'joining_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
