<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    /**
     * Run the database seeds for default leave types.
     */
    public function run(): void
    {
        $leaveTypes = [
            [
                'name' => 'Casual Leave',
                'max_days_per_year' => 12,
                'description' => 'Used for urgent personal matters, short trips, or family events.',
                'is_active' => true,
            ],
            [
                'name' => 'Sick Leave',
                'max_days_per_year' => 10,
                'description' => 'Allocated for employee illness, medical appointments, or recovery.',
                'is_active' => true,
            ],
            [
                'name' => 'Earned Leave',
                'max_days_per_year' => 15,
                'description' => 'Accumulated annual leaves that employees can request for extended vacations.',
                'is_active' => true,
            ],
            [
                'name' => 'Maternity / Paternity Leave',
                'max_days_per_year' => 90,
                'description' => 'Granted to new parents to care for and bond with their newborn child.',
                'is_active' => true,
            ],
        ];

        foreach ($leaveTypes as $type) {
            LeaveType::firstOrCreate(
                ['name' => $type['name']],
                [
                    'max_days_per_year' => $type['max_days_per_year'],
                    'description' => $type['description'],
                    'is_active' => $type['is_active'],
                ]
            );
        }
    }
}
