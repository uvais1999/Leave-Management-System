<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define specific capabilities/permissions as requested
        $permissions = [
            // Admin Capabilities
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',

            'roles.assign',            // Assign roles
            'leaves.view-all',         // View all leave applications
            'leave-types.manage',      // Full CRUD on leave types

            // Manager Capabilities
            'leaves.view-subordinates', // View leave applications of employees under them
            'leaves.approve',          // Approve leaves
            'leaves.reject',           // Reject leaves

            // Employee Capabilities (Managers also inherit these as they can apply for leaves too)
            'leaves.create',            // Apply for leave
            'leaves.view',         // View own leave history
            'leaves.update',     // Edit pending leaves only
            'leaves.delete',   // Delete pending leaves only
        ];

        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName);
        }

        // Create roles and sync exact permissions
        
        // 1. Admin Role (Full company management, view all leaves, approve/reject, manage leave types/users)
        $adminRole = Role::findOrCreate('Admin');
        $adminRole->syncPermissions([
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'roles.assign',
            'leaves.view-all',
            'leave-types.manage',
            'leaves.approve',
            'leaves.reject',
        ]);

        // 2. Manager Role (View subordinate leaves, approve/reject leaves only)
        $managerRole = Role::findOrCreate('Manager');
        $managerRole->syncPermissions([
            'leaves.view-subordinates',
            'leaves.approve',
            'leaves.reject',
        ]);

        // 3. Employee Role (Create, view, update, and delete their own pending leaves only)
        $employeeRole = Role::findOrCreate('Employee');
        $employeeRole->syncPermissions([
            'leaves.create',
            'leaves.view',
            'leaves.update',
            'leaves.delete',
        ]);
    }
}
