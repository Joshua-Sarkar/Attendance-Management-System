<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\PayrollSetting;
use App\Models\PayrollProfile;
use App\Models\PayrollCycle;
use App\Models\PayrollRecord;
use App\Models\PayrollCorrection;
use App\Models\PayrollException;
use App\Models\EmployeeProfile;
use App\Services\PayrollService;
use Carbon\Carbon;

class PayrollSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed defaults
        PayrollSetting::seedDefaults();

        // 2. Ensure Admin exists
        $admin = User::where('role', 'admin')->first();
        if (!$admin) {
            $admin = User::create([
                'name' => 'Rhea Sarin',
                'email' => 'admin@venturerequest.com',
                'password' => bcrypt('password123'),
                'role' => 'admin',
                'status' => 'active',
                'joining_date' => '2025-01-15',
            ]);
        }

        // 3. Make sure we have a few employees with payroll profiles and realistic salaries
        $employees = User::where('role', 'employee')->get();
        
        // If there are no employees, let's create a few matching the BRS mock data
        if ($employees->isEmpty()) {
            $mockData = [
                ['name' => 'Ananya Rawat', 'email' => 'ananya@example.com', 'base' => 52000, 'joining' => '2025-03-20', 'designation' => 'Backend Engineer'],
                ['name' => 'Kabir Mehta', 'email' => 'kabir@example.com', 'base' => 46000, 'joining' => '2025-10-15', 'designation' => 'Product Designer'],
                ['name' => 'Simran Kaur', 'email' => 'simran@example.com', 'base' => 42000, 'joining' => '2024-05-12', 'designation' => 'Content Strategist'],
                ['name' => 'Devansh Bhatt', 'email' => 'devansh@example.com', 'base' => 64000, 'joining' => '2025-02-01', 'designation' => 'DevOps Lead'],
                ['name' => 'Priya Nautiyal', 'email' => 'priya@example.com', 'base' => 38000, 'joining' => '2026-04-10', 'designation' => 'Ops Coordinator'],
                ['name' => 'Yash Semwal', 'email' => 'yash@example.com', 'base' => 36000, 'joining' => '2026-05-18', 'designation' => 'Sales Executive'],
                ['name' => 'Ira Thapliyal', 'email' => 'ira@example.com', 'base' => 44000, 'joining' => '2024-11-20', 'designation' => 'UI Designer'],
                ['name' => 'Arjun Bisht', 'email' => 'arjun@example.com', 'base' => 50000, 'joining' => '2026-06-18', 'designation' => 'Frontend Engineer'], // Joined June 18 2026 -> BRS Transition Bridge case!
            ];

            foreach ($mockData as $idx => $m) {
                $user = User::create([
                    'employee_id' => 'EMP-' . (1042 + $idx),
                    'name' => $m['name'],
                    'email' => $m['email'],
                    'password' => bcrypt('password123'),
                    'role' => 'employee',
                    'status' => 'active',
                    'joining_date' => $m['joining'],
                ]);

                // Create employee profile
                EmployeeProfile::create([
                    'user_id' => $user->id,
                    'designation' => $m['designation'],
                    'employee_category' => 'Permanent',
                    'office_landline' => '0135-223432',
                ]);

                $employees->push($user);
            }
            // Re-fetch employees
            $employees = User::where('role', 'employee')->get();
        }

        foreach ($employees as $idx => $emp) {
            $baseSalary = 35000 + (($idx * 4000) % 35000);
            
            // Create payroll profile
            $payroll = PayrollProfile::updateOrCreate(
                ['user_id' => $emp->id],
                [
                    'base_salary' => $baseSalary,
                    'salary_effective_date' => $emp->joining_date ?? '2026-01-01',
                    'payroll_enabled' => true,
                    'import_source' => 'Manual',
                ]
            );

            // Record initial salary history
            $payroll->salaryHistories()->firstOrCreate([
                'base_salary' => $baseSalary,
                'salary_effective_date' => $emp->joining_date ?? '2026-01-01',
                'change_reason' => 'Initial placement',
                'source' => 'Manual',
            ]);
        }

        // 4. Generate the "June 2026" payroll cycle
        $cycle = PayrollService::processCycle('June 2026', $admin);

        // Add a mock manual correction to Simran Kaur or Kabir Mehta for testing the Corrections tab
        $simran = User::where('name', 'Simran Kaur')->first();
        if ($simran) {
            $record = PayrollRecord::where('payroll_cycle_id', $cycle->id)
                ->where('user_id', $simran->id)
                ->first();
            if ($record) {
                PayrollService::submitCorrection($record, $record->net_salary + 2500, "Approved Slack WFH credit by manager", $admin);
            }
        }
    }
}
