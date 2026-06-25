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
        // 1. Ensure 'leave_credits' table exists
        if (!Schema::hasTable('leave_credits')) {
            Schema::create('leave_credits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('credit_type');
                $table->decimal('amount', 8, 2)->default(1.00);
                $table->decimal('used_amount', 8, 2)->default(0.00);
                $table->string('status')->default('active');
                $table->date('unlocked_at');
                $table->date('expires_at');
                $table->string('source_identifier');
                $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->json('source_metadata')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'source_identifier']);
            });
        }

        // 2. Ensure 'leave_credit_id' column exists in 'leave_requests'
        if (Schema::hasTable('leave_requests') && !Schema::hasColumn('leave_requests', 'leave_credit_id')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->foreignId('leave_credit_id')->nullable()->constrained('leave_credits')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('leave_requests') && Schema::hasColumn('leave_requests', 'leave_credit_id')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->dropForeign(['leave_credit_id']);
                $table->dropColumn('leave_credit_id');
            });
        }

        Schema::dropIfExists('leave_credits');
    }
};
