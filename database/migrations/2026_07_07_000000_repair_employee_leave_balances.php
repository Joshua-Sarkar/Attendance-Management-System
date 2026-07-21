<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\LeaveBalance;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Deterministic, environment-independent safety reconciliation.
     * File-based spreadsheet imports are decoupled into Artisan commands.
     */
    public function up(): void
    {
        // Safety reconciliation: Ensure user.leave_balance stays in sync with LeaveBalance.remaining_leave
        $balances = LeaveBalance::all();
        foreach ($balances as $lb) {
            $user = $lb->user;
            if ($user && (float)$user->leave_balance !== (float)$lb->remaining_leave) {
                $user->leave_balance = $lb->remaining_leave;
                $user->saveQuietly();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safety reconciliation is non-destructive
    }
};
