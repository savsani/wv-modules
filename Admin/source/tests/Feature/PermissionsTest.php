<?php

use App\Models\User;
use Modules\Admin\Database\Seeders\AdminPermissionSeeder;
use Modules\Auth\Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed([AdminPermissionSeeder::class, RoleSeeder::class]);
});

test('guests are redirected to login', function () {
    $this->get('/admin/permissions')->assertRedirect('/login');
});

test('admins can view the permissions catalog', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get('/admin/permissions');

    $response->assertOk();
    $response->assertSee('permissions.view');
    $response->assertSee('View Permissions');
    $response->assertSee('roles.create');
});

test('non-admins cannot view the permissions catalog', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)->get('/admin/permissions')->assertForbidden();
});
