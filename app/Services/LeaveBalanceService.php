<?php

namespace App\Services;

use App\Models\User;
use App\Models\LeaveLedgerEntry;
use Illuminate\Support\Facades\DB;

class LeaveBalanceService
{
    /**
     * Initialize leave balance for a new employee.
     */
    public static function initializeUser(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->leave_balance = 2.00;
            $user->save();

            LeaveLedgerEntry::create([
                'user_id' => $user->id,
                'amount' => 2.00,
                'type' => 'opening_balance',
                'description' => 'Opening leave balance',
            ]);
        });
    }
}
