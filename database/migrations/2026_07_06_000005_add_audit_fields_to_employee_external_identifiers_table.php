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
        Schema::table('employee_external_identifiers', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('identifier_type');
            $table->foreignId('verified_by_id')->nullable()->after('is_active')->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable()->after('verified_by_id');
            $table->text('notes')->nullable()->after('verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_external_identifiers', function (Blueprint $table) {
            $table->dropForeign(['verified_by_id']);
            $table->dropColumn(['is_active', 'verified_by_id', 'verified_at', 'notes']);
        });
    }
};
