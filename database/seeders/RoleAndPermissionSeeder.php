<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'manage-centers',
            'manage-users',
            'manage-settings',
            'manage-center',
            'import-csv',
            'manage-templates',
            'view-records',
            'view-reports',
            'view-notifications',
            'view-audit-logs',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $superAdmin = Role::findOrCreate('super-admin');
        $superAdmin->syncPermissions($permissions);

        $admin = Role::findOrCreate('admin');
        $admin->syncPermissions(array_values(array_diff($permissions, ['manage-centers'])));

        $operator = Role::findOrCreate('operator');
        $operator->syncPermissions([
            'import-csv',
            'view-reports',
            'view-notifications',
        ]);
    }
}
