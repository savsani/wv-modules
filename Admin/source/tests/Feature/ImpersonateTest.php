<?php

use App\Models\User;
use Modules\Admin\Database\Seeders\AdminPermissionSeeder;
use Modules\Auth\Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed([AdminPermissionSeeder::class, RoleSeeder::class]);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->user = User::factory()->create();
    $this->user->assignRole('user');
});

test('guests are redirected to login', function () {
    $this->post("/admin/users/{$this->user->id}/impersonate")->assertRedirect('/login');
});

test('non-admins cannot impersonate', function () {
    $this->actingAs($this->user)
        ->post("/admin/users/{$this->admin->id}/impersonate")
        ->assertForbidden();
});

test('admins can impersonate a user and the banner is shown on other pages', function () {
    $response = $this->actingAs($this->admin)->post("/admin/users/{$this->user->id}/impersonate");

    $response->assertRedirect(route('dashboard'));
    expect(auth()->id())->toBe($this->user->id)
        ->and(session('impersonator_id'))->toBe($this->admin->id);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Return to your account')
        ->assertSee($this->user->name);
});

test('admins cannot impersonate themselves', function () {
    $this->actingAs($this->admin)
        ->post("/admin/users/{$this->admin->id}/impersonate")
        ->assertForbidden();
});

test('admins cannot impersonate another admin', function () {
    $otherAdmin = User::factory()->create();
    $otherAdmin->assignRole('admin');

    $this->actingAs($this->admin)
        ->post("/admin/users/{$otherAdmin->id}/impersonate")
        ->assertForbidden();
});

test('cannot start a second impersonation while already impersonating', function () {
    $anotherUser = User::factory()->create();
    $anotherUser->assignRole('user');

    $this->actingAs($this->admin)->post("/admin/users/{$this->user->id}/impersonate");

    $this->post("/admin/users/{$anotherUser->id}/impersonate")->assertForbidden();
});

test('an impersonated session can return to the original account', function () {
    $this->actingAs($this->admin)->post("/admin/users/{$this->user->id}/impersonate");

    $response = $this->post('/impersonate/stop');

    $response->assertRedirect(route('admin.users.index'));
    expect(auth()->id())->toBe($this->admin->id)
        ->and(session()->has('impersonator_id'))->toBeFalse();
});

test('stopping impersonation is forbidden when not impersonating', function () {
    $this->actingAs($this->user)
        ->post('/impersonate/stop')
        ->assertForbidden();
});
