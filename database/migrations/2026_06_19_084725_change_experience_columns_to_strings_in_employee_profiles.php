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
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->string('previous_year_experience')->nullable()->change();
            $table->string('years_completed')->nullable()->change();
            $table->string('overall_year_experience')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->decimal('previous_year_experience', 5, 2)->nullable()->change();
            $table->decimal('years_completed', 5, 2)->nullable()->change();
            $table->decimal('overall_year_experience', 5, 2)->nullable()->change();
        });
    }
};

