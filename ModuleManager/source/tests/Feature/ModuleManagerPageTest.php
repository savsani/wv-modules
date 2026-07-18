<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Modules\Auth\Database\Seeders\RoleSeeder;
use Modules\ModuleManager\Database\Seeders\ModuleManagerPermissionSeeder;

beforeEach(function () {
    $this->seed([ModuleManagerPermissionSeeder::class, RoleSeeder::class]);

    // ModuleManagerController::index() checks the latest version of every
    // registered module — fake that away so tests never hit the real
    // network, matching Modules/ModuleManager's own ModuleVersionChecker.
    Http::fake(['raw.githubusercontent.com/*' => Http::response('', 404)]);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('guests are redirected to login', function () {
    $this->get('/admin/module-manager')->assertRedirect('/login');
});

test('non-admins cannot view the module manager page', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)->get('/admin/module-manager')->assertForbidden();
});

test('admins can view the module manager page with registered modules listed', function () {
    $response = $this->actingAs($this->admin)->get('/admin/module-manager');

    $response->assertOk();
    $response->assertSee('Core');
    $response->assertSee('Auth');
});
