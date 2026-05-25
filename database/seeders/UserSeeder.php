<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds to populate default and dummy users.
     */
    public function run(): void
    {
        // 1. Create the Admin user and assign the Admin role, avoiding duplicates
        $admin = User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('Admin@123'),
            ]
        );
        $admin->assignRole('Admin');

        // 2. Create a Manager user to establish direct reporting structure
        $manager = User::firstOrCreate(
            ['email' => 'manager@test.com'],
            [
                'name' => 'Manager John',
                'password' => bcrypt('Manager@123'),
            ]
        );
        $manager->assignRole('Manager');

        // 3. Create 10 dummy Employee users reporting to the manager
        $employeeNames = [
            'Alice Smith',
            'Bob Jones',
            'Charlie Brown',
            'David Beckham',
            'Emily Watson',
            'Frank Miller',
            'Grace Hopper',
            'Henry Cavill',
            'Isabella Ross',
            'Jack Reacher'
        ];

        foreach ($employeeNames as $index => $name) {
            $empNumber = $index + 1;
            $employee = User::firstOrCreate(
                ['email' => "employee{$empNumber}@test.com"],
                [
                    'name' => $name,
                    'password' => bcrypt('Employee@123'),
                    'manager_id' => $manager->id, // Assign reporting manager
                    'is_active' => true,
                ]
            );
            $employee->assignRole('Employee');
        }
    }
}
