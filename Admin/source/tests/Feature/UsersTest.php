<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Modules\Admin\Database\Seeders\AdminPermissionSeeder;
use Modules\Auth\Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed([AdminPermissionSeeder::class, RoleSeeder::class]);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->userRole = Role::where('name', 'user')->firstOrFail();
});

test('guests are redirected to login', function () {
    $this->get('/admin/users')->assertRedirect('/login');
});

test('non-admins cannot view the users page', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)->get('/admin/users')->assertForbidden();
});

test('admins can view the users list', function () {
    $user = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
    $user->assignRole('user');

    $response = $this->actingAs($this->admin)->get('/admin/users');

    $response->assertOk();
    $response->assertSee('Jane Doe');
    $response->assertSee('jane@example.com');
});

test('admins can create a user, which is verified and role-assigned immediately', function () {
    $response = $this->actingAs($this->admin)->postJson('/admin/users', [
        'name' => 'New User',
        'email' => 'new-user@example.com',
        'password' => 'New-Password1',
        'password_confirmation' => 'New-Password1',
        'role_id' => $this->userRole->id,
        'is_active' => true,
    ]);

    $response->assertCreated();
    $response->assertJsonFragment(['name' => 'New User', 'email' => 'new-user@example.com']);

    $user = User::where('email', 'new-user@example.com')->firstOrFail();
    expect($user->email_verified_at)->not->toBeNull()
        ->and($user->hasRole('user'))->toBeTrue()
        ->and($user->is_active)->toBeTrue();
});

test('creating a user requires matching password confirmation', function () {
    $response = $this->actingAs($this->admin)->postJson('/admin/users', [
        'name' => 'New User',
        'email' => 'mismatch@example.com',
        'password' => 'New-Password1',
        'password_confirmation' => 'Different-Password1',
        'role_id' => $this->userRole->id,
        'is_active' => true,
    ]);

    $response->assertJsonValidationErrors('password');
});

test('admins can update a user without changing the password', function () {
    $user = User::factory()->create(['password' => bcrypt('Original-Password1')]);
    $user->assignRole('user');
    $originalPassword = $user->password;

    $response = $this->actingAs($this->admin)->putJson("/admin/users/{$user->id}", [
        'name' => 'Updated Name',
        'email' => $user->email,
        'password' => '',
        'password_confirmation' => '',
        'role_id' => $this->userRole->id,
        'is_active' => false,
    ]);

    $response->assertOk();

    $user->refresh();
    expect($user->name)->toBe('Updated Name')
        ->and($user->password)->toBe($originalPassword)
        ->and($user->is_active)->toBeFalse();
});

test('admins can change a user password during update', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($this->admin)->putJson("/admin/users/{$user->id}", [
        'name' => $user->name,
        'email' => $user->email,
        'password' => 'Brand-New-Password1',
        'password_confirmation' => 'Brand-New-Password1',
        'role_id' => $this->userRole->id,
        'is_active' => true,
    ])->assertOk();

    expect(Hash::check('Brand-New-Password1', $user->refresh()->password))->toBeTrue();
});

test('admins cannot delete their own account', function () {
    $response = $this->actingAs($this->admin)->deleteJson("/admin/users/{$this->admin->id}");

    $response->assertStatus(422);
    $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
});

test('admins can delete another user', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $response = $this->actingAs($this->admin)->deleteJson("/admin/users/{$user->id}");

    $response->assertOk();
    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

test('admins can force-disable a user\'s two-factor authentication', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $user->forceFill([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_recovery_codes' => encrypt(json_encode(['code'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    $response = $this->actingAs($this->admin)->postJson("/admin/users/{$user->id}/disable-two-factor");

    $response->assertOk();

    $user->refresh();
    expect($user->two_factor_secret)->toBeNull()
        ->and($user->two_factor_recovery_codes)->toBeNull()
        ->and($user->two_factor_confirmed_at)->toBeNull();
});
