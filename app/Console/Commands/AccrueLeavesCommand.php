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
        
        $monthYear = now()->format('F Y');

        try {
            $count = \App\Services\LeaveBalanceService::accrueMonthlyLeaves();
            
            $msg = "Successfully accrued 2 leaves for {$count} users for {$monthYear}.";
            $this->info($msg);
            Log::info("leaves:accrue executed successfully. {$msg}");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to accrue leaves: " . $e->getMessage());
            Log::error("leaves:accrue failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
