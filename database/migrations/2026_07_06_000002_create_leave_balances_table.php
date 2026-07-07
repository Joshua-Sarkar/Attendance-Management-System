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
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            
            // Detailed leave balance fields
            $table->decimal('planned_leave', 8, 2)->default(0.00);
            $table->decimal('unplanned_leave', 8, 2)->default(0.00);
            $table->decimal('paternity_leave', 8, 2)->default(0.00);
            $table->decimal('maternity_leave', 8, 2)->default(0.00);
            $table->decimal('compensatory_leave', 8, 2)->default(0.00);
            $table->decimal('pending_leave', 8, 2)->default(0.00);
            $table->decimal('utilized_leave', 8, 2)->default(0.00);
            $table->decimal('carry_forward', 8, 2)->default(0.00);
            $table->decimal('remaining_leave', 8, 2)->default(0.00);

            // Import Metadata
            $table->timestamp('last_imported_at')->nullable();
            $table->foreignId('imported_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('import_source')->default('Manual');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};
