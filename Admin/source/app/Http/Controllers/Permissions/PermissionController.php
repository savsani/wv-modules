<?php

namespace Modules\Admin\Http\Controllers\Permissions;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Display the read-only permissions catalog.
     */
    public function index(): View
    {
        $permissions = Permission::withCount('roles')
            ->orderBy('name')
            ->get()
            ->map(fn (Permission $permission): array => [
                'id' => $permission->id,
                'name' => $permission->name,
                'display_name' => $permission->display_name,
                'module' => Str::of($permission->name)->before('.')->headline()->toString(),
                'roles_count' => $permission->roles_count,
            ]);

        return view('admin::permissions.index', ['permissions' => $permissions]);
    }
}
