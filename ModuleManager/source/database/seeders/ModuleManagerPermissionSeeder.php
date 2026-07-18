<?php

namespace Modules\ModuleManager\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ModuleManagerPermissionSeeder extends Seeder
{
    /**
     * Permissions gating this module's routes, keyed by name => display name.
     * The part before the first dot is used as the "module" grouping in the
     * admin Permissions and Roles UI (e.g. "modules.install" groups under
     * "Modules").
     *
     * @var array<string, string>
     */
    public const PERMISSIONS = [
        'modules.view' => 'View Modules',
        'modules.install' => 'Install Modules',
        'modules.update' => 'Update Modules',
        'modules.migrate' => 'Run Module Migrations',
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
