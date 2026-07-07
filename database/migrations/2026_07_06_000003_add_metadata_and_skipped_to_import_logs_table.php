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
        if (Schema::hasTable('import_logs')) {
            Schema::table('import_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('import_logs', 'skipped_count')) {
                    $table->integer('skipped_count')->default(0)->after('updated_count');
                }
                if (!Schema::hasColumn('import_logs', 'duration_seconds')) {
                    $table->decimal('duration_seconds', 8, 2)->nullable()->after('error_count');
                }
                if (!Schema::hasColumn('import_logs', 'metadata')) {
                    $table->json('metadata')->nullable()->after('errors');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('import_logs')) {
            Schema::table('import_logs', function (Blueprint $table) {
                $table->dropColumn(['skipped_count', 'duration_seconds', 'metadata']);
            });
        }
    }
};
