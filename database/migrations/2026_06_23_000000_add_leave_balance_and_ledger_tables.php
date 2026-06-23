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
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('leave_balance', 8, 2)->default(0.00)->after('status');
        });

        Schema::create('leave_ledger_entries', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            
            $table->foreignId('leave_request_id')
                ->nullable()
                ->constrained('leave_requests')
                ->cascadeOnDelete();
            
            $table->decimal('amount', 8, 2);
            $table->string('type'); // opening_balance, accrual, deduction, refund, adjustment
            $table->string('description')->nullable();
            
            $table->timestamps();

            $table->index('user_id');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_ledger_entries');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('leave_balance');
        });
    }
};
