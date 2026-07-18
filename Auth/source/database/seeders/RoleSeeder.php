<?php

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = Role::findOrCreate('admin', 'web');
        $admin->update(['display_name' => 'Administrator']);
        $admin->syncPermissions(Permission::all());

        $user = Role::findOrCreate('user', 'web');
        $user->update(['display_name' => 'User']);
        $user->syncPermissions([]);
    }
}
