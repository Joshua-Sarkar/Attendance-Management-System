<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\LeaveLedgerEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccrueLeavesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leaves:accrue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Accrue 2 leave credits monthly for all active employees';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting monthly leave accrual...');
        
        $users = User::where('status', 'active')
            ->whereIn('role', ['employee', 'manager'])
            ->get();

        $count = 0;
        $monthYear = now()->format('F Y');

        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        foreach ($users as $user) {
            // Check if user already has an accrual for this calendar month
            $alreadyAccrued = LeaveLedgerEntry::where('user_id', $user->id)
                ->where('type', 'accrual')
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->exists();

            if ($alreadyAccrued) {
                $this->line("User {$user->name} already accrued for this month. Skipping.");
                continue;
            }

            DB::transaction(function () use ($user, $monthYear) {
                // Increment cached balance
                $user->leave_balance += 2.00;
                $user->save();

                // Create ledger entry
                LeaveLedgerEntry::create([
                    'user_id' => $user->id,
                    'amount' => 2.00,
                    'type' => 'accrual',
                    'description' => "Monthly accrual for {$monthYear}",
                ]);
            });

            $count++;
        }

        $msg = "Successfully accrued 2 leaves for {$count} users for {$monthYear}.";
        $this->info($msg);
        Log::info("leaves:accrue executed successfully. {$msg}");

        return Command::SUCCESS;
    }
}
