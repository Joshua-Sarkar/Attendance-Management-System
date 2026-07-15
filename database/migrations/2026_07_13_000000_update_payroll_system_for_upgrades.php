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
        Schema::table('payroll_records', function (Blueprint $table) {
            $table->integer('calculation_version')->default(1)->after('calculation_metadata');
            $table->string('fingerprint')->nullable()->after('calculation_version');
            $table->string('employee_review_status')->default('pending')->after('fingerprint'); // pending, approved, disputed, stale
            $table->timestamp('employee_approved_at')->nullable()->after('employee_review_status');
            $table->timestamp('admin_approved_at')->nullable()->after('employee_approved_at');
            $table->foreignId('admin_approved_by_id')->nullable()->after('admin_approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable()->after('admin_approved_by_id');
            $table->foreignId('locked_by_id')->nullable()->after('locked_at')->constrained('users')->nullOnDelete();
            $table->longText('locked_snapshot')->nullable()->after('locked_by_id');
        });

        Schema::create('payroll_disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_record_id')->constrained('payroll_records')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('category'); // Attendance, Leave, Salary, Deduction, Other
            $table->date('affected_date')->nullable();
            $table->text('description');
            $table->text('expected_correction');
            $table->string('status')->default('open'); // open, resolved, rejected
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_disputes');

        Schema::table('payroll_records', function (Blueprint $table) {
            $table->dropForeign(['admin_approved_by_id']);
            $table->dropForeign(['locked_by_id']);
            $table->dropColumn([
                'calculation_version',
                'fingerprint',
                'employee_review_status',
                'employee_approved_at',
                'admin_approved_at',
                'admin_approved_by_id',
                'locked_at',
                'locked_by_id',
                'locked_snapshot'
            ]);
        });
    }
};
