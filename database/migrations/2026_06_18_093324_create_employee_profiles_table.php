<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employee_profiles', function (Blueprint $table) {
            $table->id();
            
            // Foreign key relation with users
            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();

            // Personal
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('marital_status')->nullable();
            $table->date('date_of_marriage')->nullable();
            $table->string('nationality')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('personal_email')->nullable();
            $table->string('mobile_no')->nullable();

            // Government IDs
            $table->string('pf_uan')->nullable();
            $table->string('passport_no')->nullable();
            $table->text('aadhar_card')->nullable(); // encrypted
            $table->text('pan')->nullable(); // encrypted
            $table->string('pf_no')->nullable();
            $table->string('esi_number')->nullable();
            $table->date('date_of_gratuity')->nullable();

            // Employment
            $table->string('payroll_type')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->string('office_landline')->nullable();
            $table->string('leave_rule')->nullable();
            $table->string('shift')->nullable();
            $table->string('designation')->nullable();
            $table->string('grade')->nullable();
            $table->string('employee_type')->nullable();
            $table->string('company')->nullable();
            $table->string('location')->nullable();
            $table->string('biometric_id')->nullable();
            $table->string('hiring_source')->nullable();
            $table->string('source_of_verification')->nullable();

            // Current address
            $table->string('current_address1')->nullable();
            $table->string('current_address2')->nullable();
            $table->string('current_country')->nullable();
            $table->string('current_state')->nullable();
            $table->string('current_city')->nullable();
            $table->string('current_zip')->nullable();

            // Permanent address
            $table->string('permanent_address1')->nullable();
            $table->string('permanent_address2')->nullable();
            $table->string('permanent_country')->nullable();
            $table->string('permanent_state')->nullable();
            $table->string('permanent_city')->nullable();
            $table->string('permanent_zip')->nullable();
            $table->boolean('same_as_current_address')->nullable();

            // Bank
            $table->string('payment_type')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_holder_name')->nullable();
            $table->text('account_no')->nullable(); // encrypted
            $table->text('ifsc_code')->nullable(); // encrypted

            // Emergency contact
            $table->string('emergency_name')->nullable();
            $table->string('emergency_relationship')->nullable();
            $table->string('emergency_address')->nullable();
            $table->string('emergency_email')->nullable();
            $table->string('emergency_mobile')->nullable();

            // Education
            $table->string('degree_name')->nullable();
            $table->string('institution_name')->nullable();
            $table->string('passing_year')->nullable();
            $table->string('percentage')->nullable();

            // Previous employment
            $table->string('previous_company_name')->nullable();
            $table->string('previous_job_title')->nullable();
            $table->date('previous_from_date')->nullable();
            $table->date('previous_to_date')->nullable();

            // Tenure
            $table->string('probation_period')->nullable();
            $table->date('probation_confirm_date')->nullable();
            $table->date('separation_date')->nullable();
            $table->date('last_working_day')->nullable();
            $table->decimal('previous_year_experience', 5, 2)->nullable();
            $table->decimal('years_completed', 5, 2)->nullable();
            $table->decimal('overall_year_experience', 5, 2)->nullable();

            // Additional fields
            $table->string('city_type')->nullable();
            $table->integer('notice_days')->nullable();
            $table->string('state_name')->nullable();
            $table->date('joining_date')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_profiles');
    }
};
