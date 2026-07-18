<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Modules\Admin\Database\Seeders\AdminPermissionSeeder;
use Modules\Auth\Database\Seeders\RoleSeeder;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('guests visiting the root url are redirected to login', function () {
    $response = $this->get('/');

    $response->assertRedirect('/login');
});

test('regular users visiting the root url are redirected to the dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/');

    $response->assertRedirect(route('dashboard', absolute: false));
});

test('admins visiting the root url are also redirected to the dashboard', function () {
    $this->seed([AdminPermissionSeeder::class, RoleSeeder::class]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get('/');

    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('an admin login also redirects to the dashboard', function () {
    $this->seed([AdminPermissionSeeder::class, RoleSeeder::class]);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->post('/login', [
        'email' => $admin->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($admin);
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('inactive users cannot authenticate using the login screen', function () {
    $user = User::factory()->create(['is_active' => false]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors('email');
});

test('inactive users are immediately logged out on their next request', function () {
    $user = User::factory()->create();

    $this->actingAs($user);
    $this->assertAuthenticatedAs($user);

    $user->update(['is_active' => false]);

    $response = $this->get('/dashboard');

    $this->assertGuest();
    $response->assertRedirect(route('login'));
    $this->assertSame('account-deactivated', session('status'));
});

test('users cannot authenticate with an invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('login is rate limited after five failed attempts', function () {
    $user = User::factory()->create();

    RateLimiter::clear(Str::transliterate(Str::lower($user->email).'|127.0.0.1'));

    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);
    }

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertStatus(429);
    $this->assertGuest();
});

test('remember me sets a persistent session', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
        'remember' => 'on',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertCookie(auth()->guard()->getRecallerName());
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
