<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RbacLimitedUserSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guardName = 'web';

        $role = Role::firstOrCreate([
            'name' => 'limited_admin',
            'guard_name' => $guardName,
        ]);

        $permission = Permission::firstOrCreate([
            'name' => 'users.view',
            'guard_name' => $guardName,
        ]);

        $role->syncPermissions([
            $permission,
        ]);

        $user = User::updateOrCreate(
            ['email' => 'limited@school-erp.local'],
            [
                'name' => 'Limited Admin',
                'password' => Hash::make('23250077'),
            ],
        );

        $user->syncRoles([
            $role,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
