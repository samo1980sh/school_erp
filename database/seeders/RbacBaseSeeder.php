<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RbacBaseSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $guardName = 'web';

        $superAdminRole = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => $guardName,
        ]);

        $adminEmail = env('SEED_SUPER_ADMIN_EMAIL', 'admin@school-erp.local');

        $admin = User::where('email', $adminEmail)->first();

        if (! $admin) {
            throw new \RuntimeException("Super admin user not found: {$adminEmail}");
        }

        if (! $admin->hasRole($superAdminRole)) {
            $admin->assignRole($superAdminRole);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
