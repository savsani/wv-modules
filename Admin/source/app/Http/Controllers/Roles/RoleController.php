<?php

namespace Modules\Admin\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Admin\Support\ActivityLogger;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display the roles list with CRUD support.
     */
    public function index(): View
    {
        $protectedRoles = config('permission.protected_roles', []);

        $roles = Role::with('permissions:id')
            ->withCount(['permissions', 'users'])
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role): array => $this->transform($role, $role->permissions->pluck('id')->all(), $protectedRoles));

        $permissionGroups = Permission::orderBy('name')
            ->get()
            ->groupBy(fn (Permission $permission) => str($permission->name)->before('.')->toString())
            ->map(fn ($permissions, $module) => [
                'label' => str($module)->headline()->toString(),
                'permissions' => $permissions->map(fn (Permission $permission): array => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'display_name' => $permission->display_name,
                ])->values(),
            ])
            ->values();

        return view('admin::roles.index', [
            'roles' => $roles,
            'permissionGroups' => $permissionGroups,
            'totalPermissions' => Permission::count(),
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validateRole($request, forCreate: true);

        $role = Role::create([
            'name' => $data['name'],
            'display_name' => $data['display_name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($data['permission_ids']);

        ActivityLogger::record('admin', 'role_created', "Created role \"{$role->display_name}\" ({$role->name}).");

        return response()->json($this->transform(
            $role->loadCount(['permissions', 'users']),
            $data['permission_ids'],
            config('permission.protected_roles', [])
        ), 201);
    }

    /**
     * Update the given role's display name and permissions. The role name
     * itself is immutable once created, so it is intentionally not accepted here.
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        $data = $this->validateRole($request, forCreate: false);

        $before = [
            'display_name' => $role->display_name,
            'permissions' => $role->permissions->pluck('name')->sort()->values()->all(),
        ];

        $role->update(['display_name' => $data['display_name']]);
        $role->syncPermissions($data['permission_ids']);

        $after = [
            'display_name' => $role->display_name,
            'permissions' => $role->permissions()->pluck('name')->sort()->values()->all(),
        ];

        ActivityLogger::record(
            'admin',
            'role_updated',
            "Updated role \"{$role->display_name}\" ({$role->name}).",
            properties: ['before' => $before, 'after' => $after],
        );

        return response()->json($this->transform(
            $role->loadCount(['permissions', 'users']),
            $data['permission_ids'],
            config('permission.protected_roles', [])
        ));
    }

    /**
     * Remove the given role, unless it is one of the protected default roles.
     */
    public function destroy(Role $role): JsonResponse
    {
        if (in_array($role->name, config('permission.protected_roles', []), true)) {
            return response()->json([
                'message' => "The \"{$role->display_name}\" role is a default role and can't be deleted.",
            ], 422);
        }

        ActivityLogger::record('admin', 'role_deleted', "Deleted role \"{$role->display_name}\" ({$role->name}).");

        $role->delete();

        return response()->json(['message' => 'Role deleted.']);
    }

    /**
     * @return array{name: string, display_name: string, permission_ids: array<int, int>}
     */
    private function validateRole(Request $request, bool $forCreate): array
    {
        $rules = [
            'display_name' => ['required', 'string', 'max:255'],
            'permission_ids' => ['array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ];

        if ($forCreate) {
            $rules['name'] = [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('roles', 'name')->where('guard_name', 'web'),
            ];
        }

        $data = $request->validate($rules);
        $data['permission_ids'] ??= [];

        return $data;
    }

    /**
     * @param  array<int, int>  $permissionIds
     * @param  array<int, string>  $protectedRoles
     * @return array<string, mixed>
     */
    private function transform(Role $role, array $permissionIds, array $protectedRoles): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'display_name' => $role->display_name,
            'permissions_count' => $role->permissions_count,
            'users_count' => $role->users_count,
            'permission_ids' => array_values($permissionIds),
            'is_protected' => in_array($role->name, $protectedRoles, true),
        ];
    }
}
