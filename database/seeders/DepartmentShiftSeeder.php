<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentShiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Department::updateOrCreate(
            ['name' => 'Healthcare'],
            [
                'code' => 'HLT',
                'description' => 'Healthcare Department',
                'shift_start_time' => '10:00:00',
                'shift_end_time' => '18:00:00',
                'grace_minutes' => 5,
            ]
        );
    }
}
