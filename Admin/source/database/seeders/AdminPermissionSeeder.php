<?php

namespace Modules\Admin\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class AdminPermissionSeeder extends Seeder
{
    /**
     * Permissions gating this module's routes, keyed by name => display name.
     * The part before the first dot is used as the "module" grouping in the
     * admin Permissions and Roles UI (e.g. "users.edit" groups under "Users").
     *
     * @var array<string, string>
     */
    public const PERMISSIONS = [
        'permissions.view' => 'View Permissions',

        'roles.view' => 'View Roles',
        'roles.create' => 'Create Roles',
        'roles.edit' => 'Edit Roles',
        'roles.delete' => 'Delete Roles',

        'users.view' => 'View Users',
        'users.create' => 'Create Users',
        'users.edit' => 'Edit Users',
        'users.delete' => 'Delete Users',
        'users.impersonate' => 'Impersonate Users',
        'users.disable_2fa' => 'Disable Two-Factor',
        'users.verify_email' => 'Verify Email',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::PERMISSIONS as $name => $displayName) {
            Permission::findOrCreate($name, 'web')->update(['display_name' => $displayName]);
        }
    }
}
