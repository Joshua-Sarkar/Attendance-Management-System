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
        if (!Schema::hasTable('payroll_settings')) {
            Schema::create('payroll_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('payroll_cycles')) {
            Schema::create('payroll_cycles', function (Blueprint $table) {
                $table->id();
                $table->string('period'); // e.g. "June 2026"
                $table->date('start_date');
                $table->date('end_date');
                $table->string('status')->default('draft'); // draft, generated, under_review, corrections_pending, approved, locked
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('locked_at')->nullable();
                $table->foreignId('locked_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('payroll_records')) {
            Schema::create('payroll_records', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payroll_cycle_id')->constrained('payroll_cycles')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->decimal('base_salary', 12, 2)->default(0.00);
                $table->decimal('gross_salary', 12, 2)->default(0.00);
                $table->decimal('net_salary', 12, 2)->default(0.00);
                $table->decimal('attendance_deductions', 12, 2)->default(0.00);
                $table->decimal('leave_deductions', 12, 2)->default(0.00);
                $table->decimal('statutory_deductions', 12, 2)->default(0.00);
                $table->decimal('tax_deductions', 12, 2)->default(0.00);
                $table->decimal('overtime_hours', 8, 2)->default(0.00);
                $table->decimal('overtime_pay', 12, 2)->default(0.00);
                $table->decimal('bonuses', 12, 2)->default(0.00);
                $table->decimal('allowances', 12, 2)->default(0.00);
                
                // Days breakdown
                $table->integer('working_days')->default(0);
                $table->decimal('present_days', 5, 2)->default(0.00);
                $table->decimal('absent_days', 5, 2)->default(0.00);
                $table->decimal('leave_days', 5, 2)->default(0.00);
                $table->decimal('unpaid_leave_days', 5, 2)->default(0.00);
                $table->decimal('birthday_leave_days', 5, 2)->default(0.00);
                $table->integer('half_days')->default(0);
                $table->integer('late_days')->default(0);
                $table->integer('wfh_days')->default(0);
                
                $table->string('status')->default('pending'); // pending, approved, correction
                $table->string('correction_reason')->nullable();
                $table->boolean('locked')->default(false);
                $table->timestamp('last_modified_at')->nullable();
                $table->foreignId('last_modified_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->json('calculation_metadata')->nullable();
                $table->timestamps();

                $table->unique(['payroll_cycle_id', 'user_id']);
            });
        }

        if (!Schema::hasTable('payroll_corrections')) {
            Schema::create('payroll_corrections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payroll_cycle_id')->constrained('payroll_cycles')->cascadeOnDelete();
                $table->foreignId('payroll_record_id')->constrained('payroll_records')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('type')->default('correction'); // adjustment, correction, manual_override
                $table->decimal('old_net_salary', 12, 2)->default(0.00);
                $table->decimal('new_net_salary', 12, 2)->default(0.00);
                $table->decimal('financial_delta', 12, 2)->default(0.00);
                $table->text('reason');
                $table->foreignId('created_by_id')->constrained('users')->cascadeOnDelete();
                $table->string('approval_status')->default('pending'); // pending, approved, rejected
                $table->foreignId('approved_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('payroll_exceptions')) {
            Schema::create('payroll_exceptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payroll_cycle_id')->constrained('payroll_cycles')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('payroll_record_id')->nullable()->constrained('payroll_records')->cascadeOnDelete();
                $table->string('type'); // missing_salary_profile, missing_attendance, negative_salary, conflicting_attendance, etc.
                $table->text('description');
                $table->string('severity')->default('Warning'); // Critical, Warning
                $table->string('priority')->default('Medium'); // High, Medium, Low
                $table->boolean('resolved')->default(false);
                $table->timestamp('resolved_at')->nullable();
                $table->foreignId('resolved_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('payroll_audit_logs')) {
            Schema::create('payroll_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
                $table->foreignId('actor_id')->nullable()->constrained('users')->cascadeOnDelete();
                $table->string('action');
                $table->string('category'); // Attendance Override, Leave Adjustment, Payroll Correction, Salary Change, Employee Edit, Shift Change, Leave Balance Modification, Settings, Cycle
                $table->text('old_value')->nullable();
                $table->text('new_value')->nullable();
                $table->text('reason')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_audit_logs');
        Schema::dropIfExists('payroll_exceptions');
        Schema::dropIfExists('payroll_corrections');
        Schema::dropIfExists('payroll_records');
        Schema::dropIfExists('payroll_cycles');
        Schema::dropIfExists('payroll_settings');
    }
};
