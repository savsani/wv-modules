<?php

use App\Models\User;
use Modules\Admin\Database\Seeders\AdminPermissionSeeder;
use Modules\Auth\Database\Seeders\RoleSeeder;
use Nwidart\Modules\Facades\Module;

test('guests are redirected to login', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('regular users see generic dashboard content', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
    $response->assertViewHas('isAdmin', false);
    $response->assertSeeText('Your dashboard content goes here.');
});

test('admins see the admin overview with real stats at the same url', function () {
    $this->seed([AdminPermissionSeeder::class, RoleSeeder::class]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    User::factory()->count(3)->create(['is_active' => true]);
    User::factory()->count(2)->create(['is_active' => false]);

    $response = $this->actingAs($admin)->get('/dashboard');

    $response->assertOk();
    $response->assertViewHas('isAdmin', true);
    // 3 active + 2 inactive + the seeded admin = 6 total users.
    $response->assertViewHas('totalUsers', 6);
    // 3 explicitly active + the admin (active by default) = 4 active users.
    $response->assertViewHas('activeUsers', 4);
    $response->assertViewHas('totalRoles', 2); // admin + user, from RoleSeeder
    $response->assertSeeText('Roles');
    $response->assertSeeText('Permissions');
});

test('admins without the Admin module installed see generic content instead', function () {
    $this->seed([AdminPermissionSeeder::class, RoleSeeder::class]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Module::disable('Admin');

    $response = $this->actingAs($admin)->get('/dashboard');

    $response->assertOk();
    $response->assertViewHas('isAdmin', false);

    Module::enable('Admin');
});
