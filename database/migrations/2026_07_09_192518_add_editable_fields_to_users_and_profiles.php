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
            if (!Schema::hasColumn('users', 'profile_photo_path')) {
                $table->string('profile_photo_path')->nullable()->after('email');
            }
        });

        Schema::table('employee_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_profiles', 'employee_category')) {
                $table->string('employee_category')->nullable()->after('employee_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('profile_photo_path');
        });

        Schema::table('employee_profiles', function (Blueprint $table) {
            $table->dropColumn('employee_category');
        });
    }
};
