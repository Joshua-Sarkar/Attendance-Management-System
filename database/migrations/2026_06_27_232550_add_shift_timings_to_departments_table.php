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
        Schema::table('departments', function (Blueprint $table) {
            $table->time('shift_start_time')->default('09:30:00');
            $table->time('shift_end_time')->default('17:30:00');
            $table->integer('grace_minutes')->default(5);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn(['shift_start_time', 'shift_end_time', 'grace_minutes']);
        });
    }
};
