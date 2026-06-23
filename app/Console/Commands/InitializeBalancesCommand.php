<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\LeaveLedgerEntry;
use Illuminate\Support\Facades\DB;

class InitializeBalancesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leaves:initialize-balances';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize leave balances for pre-existing active employees with a starting balance of 2 credits';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting leave balance initialization...');

        // Get all active employees and managers
        $users = User::where('status', 'active')
            ->whereIn('role', ['employee', 'manager'])
            ->get();

        $count = 0;

        foreach ($users as $user) {
            // Check if opening balance already exists (idempotency check)
            $exists = LeaveLedgerEntry::where('user_id', $user->id)
                ->where('type', 'opening_balance')
                ->exists();

            if (!$exists) {
                DB::transaction(function () use ($user) {
                    $user->leave_balance = 2.00;
                    $user->save();

                    LeaveLedgerEntry::create([
                        'user_id' => $user->id,
                        'amount' => 2.00,
                        'type' => 'opening_balance',
                        'description' => 'Flat initial balance initialization',
                    ]);
                });

                $this->line("Initialized balance for {$user->name} ({$user->employee_id})");
                $count++;
            }
        }

        $this->info("Successfully initialized balances for {$count} users.");
        return Command::SUCCESS;
    }
}
