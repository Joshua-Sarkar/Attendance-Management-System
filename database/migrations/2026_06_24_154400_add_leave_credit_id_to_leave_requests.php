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
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropForeign(['leave_credit_id']);
            $table->dropColumn('leave_credit_id');
        });
    }
};
