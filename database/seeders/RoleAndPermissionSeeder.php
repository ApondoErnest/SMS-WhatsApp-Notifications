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
            'manage-users',
            'manage-settings',
            'manage-center',
            'import-csv',
            'manage-templates',
            'view-records',
            'view-reports',
            'view-notifications',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $admin = Role::findOrCreate('admin');
        $admin->syncPermissions($permissions);

        $operator = Role::findOrCreate('operator');
        $operator->syncPermissions([
            'import-csv',
            'view-reports',
            'view-notifications',
        ]);
    }
}
