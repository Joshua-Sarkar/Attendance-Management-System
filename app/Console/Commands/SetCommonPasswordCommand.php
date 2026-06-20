<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SetCommonPasswordCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'employees:set-common-password {password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set a common password for all employees who have must_change_password = 1';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $password = $this->argument('password');

        if (empty($password)) {
            $this->error('Password cannot be empty.');
            return 1;
        }

        // Only select users where must_change_password is true (1)
        // This ensures existing active users who have changed their password (must_change_password = 0) are not modified.
        $usersQuery = User::where('must_change_password', true);
        
        $count = 0;
        foreach ($usersQuery->get() as $user) {
            $user->password = Hash::make($password);
            $user->save();
            $count++;
        }

        $this->info("Updated {$count} users.");

        return 0;
    }
}
