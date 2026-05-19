<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminPanelAccessSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate([
            'name' => 'admin_panel.access',
            'guard_name' => 'web',
        ]);

        $adminRoles = [
            'super_admin',
            'system_admin',
            'school_admin',
            'academic_manager',
            'registrar',
            'accountant',
            'limited_admin',
        ];

        foreach ($adminRoles as $roleName) {
            $role = Role::where('name', $roleName)
                ->where('guard_name', 'web')
                ->first();

            if ($role) {
                $role->givePermissionTo($permission);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
