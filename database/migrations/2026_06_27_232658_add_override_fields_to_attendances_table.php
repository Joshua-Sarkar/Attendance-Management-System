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
        Schema::table('attendances', function (Blueprint $table) {
            // Convert status enum to string for maximum compatibility and extensibility (present, absent, late, on_leave, wfh, etc.)
            $table->string('status', 50)->default('absent')->change();

            // Add classification and override fields
            $table->string('classification', 50)->default('full_day');
            $table->boolean('is_overridden')->default(false);
            $table->foreignId('overridden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('overridden_at')->nullable();
            $table->text('override_reason')->nullable();
            $table->string('override_type', 50)->nullable();
            $table->string('automatic_status', 50)->nullable();
            $table->string('automatic_classification', 50)->nullable();
            $table->string('automatic_classification_reason', 100)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['overridden_by']);
            $table->dropColumn([
                'classification',
                'is_overridden',
                'overridden_by',
                'overridden_at',
                'override_reason',
                'override_type',
                'automatic_status',
                'automatic_classification',
                'automatic_classification_reason',
            ]);
            
            // Revert status to enum
            $table->enum('status', ['present', 'absent', 'late'])->default('absent')->change();
        });
    }
};
