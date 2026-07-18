<?php

use App\Models\User;
use Modules\Admin\Database\Seeders\AdminPermissionSeeder;
use Modules\Auth\Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed([AdminPermissionSeeder::class, RoleSeeder::class]);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('guests are redirected to login', function () {
    $this->get('/admin/roles')->assertRedirect('/login');
});

test('non-admins cannot view the roles page', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)->get('/admin/roles')->assertForbidden();
});

test('admins can view the roles list', function () {
    $response = $this->actingAs($this->admin)->get('/admin/roles');

    $response->assertOk();
    $response->assertSee('admin');
    $response->assertSee('Administrator');
    $response->assertSee('user');
});

test('admins can create a role with permissions', function () {
    $permissionIds = Permission::whereIn('name', ['users.view', 'users.edit'])->pluck('id');

    $response = $this->actingAs($this->admin)->postJson('/admin/roles', [
        'name' => 'support-agent',
        'display_name' => 'Support Agent',
        'permission_ids' => $permissionIds->all(),
    ]);

    $response->assertCreated();
    $response->assertJsonFragment(['name' => 'support-agent', 'display_name' => 'Support Agent']);

    $role = Role::where('name', 'support-agent')->firstOrFail();
    expect($role->permissions()->pluck('name')->sort()->values()->all())
        ->toBe(['users.edit', 'users.view']);
});

test('role name must be lowercase letters, numbers, and hyphens only', function () {
    $response = $this->actingAs($this->admin)->postJson('/admin/roles', [
        'name' => 'Support Agent!',
        'display_name' => 'Support Agent',
    ]);

    $response->assertJsonValidationErrors('name');
});

test('admins can update a role display name and permissions but not its name', function () {
    $role = Role::create(['name' => 'editor', 'display_name' => 'Editor', 'guard_name' => 'web']);
    $permissionId = Permission::where('name', 'users.view')->value('id');

    $response = $this->actingAs($this->admin)->putJson("/admin/roles/{$role->id}", [
        'name' => 'renamed-role',
        'display_name' => 'Content Editor',
        'permission_ids' => [$permissionId],
    ]);

    $response->assertOk();

    $role->refresh();
    expect($role->name)->toBe('editor')
        ->and($role->display_name)->toBe('Content Editor')
        ->and($role->permissions()->pluck('name')->all())->toBe(['users.view']);
});

test('the admin and user roles cannot be deleted', function () {
    $admin = Role::where('name', 'admin')->firstOrFail();

    $response = $this->actingAs($this->admin)->deleteJson("/admin/roles/{$admin->id}");

    $response->assertStatus(422);
    $this->assertDatabaseHas('roles', ['name' => 'admin']);
});

test('a non-protected role can be deleted', function () {
    $role = Role::create(['name' => 'editor', 'display_name' => 'Editor', 'guard_name' => 'web']);

    $response = $this->actingAs($this->admin)->deleteJson("/admin/roles/{$role->id}");

    $response->assertOk();
    $this->assertDatabaseMissing('roles', ['name' => 'editor']);
});
