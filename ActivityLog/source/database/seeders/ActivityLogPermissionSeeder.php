<?php

namespace Modules\ActivityLog\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ActivityLogPermissionSeeder extends Seeder
{
    /**
     * Permissions gating this module's routes, keyed by name => display name.
     *
     * @var array<string, string>
     */
    public const PERMISSIONS = [
        'activity-log.view' => 'View Activity Log',
        'activity-log.clear' => 'Clear Activity Log',
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
