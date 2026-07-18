<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Modules\ActivityLog\Database\Seeders\ActivityLogPermissionSeeder;
use Modules\ActivityLog\Models\ActivityLog;
use Modules\Admin\Database\Seeders\AdminPermissionSeeder;
use Modules\Auth\Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed([AdminPermissionSeeder::class, ActivityLogPermissionSeeder::class, RoleSeeder::class]);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('the mechanism works in total isolation from Auth and Admin', function () {
    event('activity.recorded', [[
        'type' => 'future-module',
        'event' => 'something_happened',
        'message' => 'A module nobody has written yet logged this.',
    ]]);

    $this->assertDatabaseHas('activity_logs', [
        'log_type' => 'future-module',
        'event' => 'something_happened',
        'message' => 'A module nobody has written yet logged this.',
    ]);
});

test('guests are redirected to login', function () {
    $this->get('/admin/activity-log')->assertRedirect('/login');
});

test('non-admins cannot view the activity log page', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)->get('/admin/activity-log')->assertForbidden();
});

test('admins can view the activity log page with existing entries', function () {
    ActivityLog::factory()->create(['message' => 'A distinctive audit message']);

    $response = $this->actingAs($this->admin)->get('/admin/activity-log');

    $response->assertOk();
    $response->assertSee('A distinctive audit message');
});

test('activity log page renders entries that carry a before/after JSON diff', function () {
    ActivityLog::factory()->create([
        'event' => 'user_updated',
        'log_type' => 'admin',
        'properties' => ['before' => ['name' => 'Old Name'], 'after' => ['name' => 'New Name']],
    ]);

    $this->actingAs($this->admin)->get('/admin/activity-log')->assertOk();
});

test('registering a new account logs an auth event', function () {
    $this->post('/register', [
        'name' => 'New User',
        'email' => 'new-user@example.com',
        'password' => 'New-Password1',
        'password_confirmation' => 'New-Password1',
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'log_type' => 'auth',
        'event' => 'registered',
        'causer_email' => 'new-user@example.com',
    ]);
});

test('a successful login logs an auth event', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'log_type' => 'auth',
        'event' => 'login_success',
        'causer_id' => $user->id,
    ]);
});

test('a failed login logs an auth event', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'log_type' => 'auth',
        'event' => 'login_failed',
        'causer_id' => $user->id,
    ]);
});

test('a failed login for an unknown email still logs the attempted email', function () {
    $this->post('/login', [
        'email' => 'nobody@example.com',
        'password' => 'wrong-password',
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'log_type' => 'auth',
        'event' => 'login_failed',
        'causer_id' => null,
        'causer_email' => 'nobody@example.com',
    ]);
});

test('logging out logs an auth event', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/logout');

    $this->assertDatabaseHas('activity_logs', [
        'log_type' => 'auth',
        'event' => 'logout',
        'causer_id' => $user->id,
    ]);
});

test('requesting a password reset link logs an auth event', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    $this->assertDatabaseHas('activity_logs', [
        'log_type' => 'auth',
        'event' => 'password_reset_link_requested',
        'causer_id' => $user->id,
    ]);
});

test('login is locked out after five failed attempts and logs an auth event', function () {
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

    $this->assertDatabaseHas('activity_logs', [
        'log_type' => 'auth',
        'event' => 'login_locked_out',
        'causer_email' => $user->email,
    ]);
});

test('resetting a password via email link logs an auth event', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'New-Password1',
            'password_confirmation' => 'New-Password1',
        ]);

        return true;
    });

    $this->assertDatabaseHas('activity_logs', [
        'log_type' => 'auth',
        'event' => 'password_reset',
        'causer_id' => $user->id,
    ]);
});

test('changing your password while authenticated logs an auth event', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put('/user/password', [
        'current_password' => 'password',
        'password' => 'New-Password1',
        'password_confirmation' => 'New-Password1',
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'log_type' => 'auth',
        'event' => 'password_changed',
        'causer_id' => $user->id,
    ]);
});

test('updating profile information logs an auth event', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put('/user/profile-information', [
        'name' => 'Updated Name',
        'email' => $user->email,
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'log_type' => 'auth',
        'event' => 'profile_updated',
        'causer_id' => $user->id,
    ]);
});

test('confirming two factor authentication logs an auth event', function () {
    $user = User::factory()->create();
    confirmPassword();

    $this->actingAs($user)->post('/user/two-factor-authentication');
    $this->actingAs($user)->post('/user/confirmed-two-factor-authentication', [
        'code' => currentOtpFor($user),
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'log_type' => 'auth',
        'event' => 'two_factor_enabled',
        'causer_id' => $user->id,
    ]);
});

test('disabling two factor authentication yourself logs an auth event', function () {
    $user = User::factory()->create();
    confirmPassword();

    $this->actingAs($user)->post('/user/two-factor-authentication');
    $this->actingAs($user)->post('/user/confirmed-two-factor-authentication', [
        'code' => currentOtpFor($user),
    ]);

    $this->actingAs($user)->delete('/user/two-factor-authentication');

    $this->assertDatabaseHas('activity_logs', [
        'log_type' => 'auth',
        'event' => 'two_factor_disabled',
        'causer_id' => $user->id,
    ]);
});

test('entering an invalid code during the two factor login challenge logs an auth event', function () {
    $user = User::factory()->create();
    confirmPassword();

    $this->actingAs($user)->post('/user/two-factor-authentication');
    $this->actingAs($user)->post('/user/confirmed-two-factor-authentication', [
        'code' => currentOtpFor($user),
    ]);

    auth()->logout();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->post('/two-factor-challenge', ['code' => '000000']);

    $this->assertDatabaseHas('activity_logs', [
        'log_type' => 'auth',
        'event' => 'two_factor_challenge_failed',
        'causer_id' => $user->id,
    ]);
});

test('regenerating two factor recovery codes logs an auth event', function () {
    $user = User::factory()->create();
    confirmPassword();

    $this->actingAs($user)->post('/user/two-factor-authentication');
    $this->actingAs($user)->post('/user/confirmed-two-factor-authentication', [
        'code' => currentOtpFor($user),
    ]);

    $this->actingAs($user)->post('/user/two-factor-recovery-codes');

    $this->assertDatabaseHas('activity_logs', [
        'log_type' => 'auth',
        'event' => 'two_factor_recovery_codes_regenerated',
        'causer_id' => $user->id,
    ]);
});

test('signing in with a recovery code logs an auth event', function () {
    $user = User::factory()->create();
    confirmPassword();

    $this->actingAs($user)->post('/user/two-factor-authentication');
    $this->actingAs($user)->post('/user/confirmed-two-factor-authentication', [
        'code' => currentOtpFor($user),
    ]);

    $recoveryCode = $user->fresh()->recoveryCodes()[0];

    auth()->logout();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->post('/two-factor-challenge', ['recovery_code' => $recoveryCode]);

    $this->assertDatabaseHas('activity_logs', [
        'log_type' => 'auth',
        'event' => 'two_factor_recovery_code_used',
        'causer_id' => $user->id,
    ]);
});

test('creating a role logs an admin event', function () {
    $this->actingAs($this->admin)->postJson('/admin/roles', [
        'name' => 'editor',
        'display_name' => 'Editor',
        'permission_ids' => [],
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'log_type' => 'admin',
        'event' => 'role_created',
        'causer_id' => $this->admin->id,
    ]);
});

test('updating a role logs the before/after display name as JSON properties', function () {
    $role = Role::create(['name' => 'editor', 'display_name' => 'Editor', 'guard_name' => 'web']);

    $this->actingAs($this->admin)->putJson("/admin/roles/{$role->id}", [
        'display_name' => 'Content Editor',
        'permission_ids' => [],
    ]);

    $log = ActivityLog::where('event', 'role_updated')->firstOrFail();

    expect($log->properties['before']['display_name'])->toBe('Editor')
        ->and($log->properties['after']['display_name'])->toBe('Content Editor');
});

test('updating and deleting a role logs admin events', function () {
    $role = Role::create(['name' => 'editor', 'display_name' => 'Editor', 'guard_name' => 'web']);

    $this->actingAs($this->admin)->putJson("/admin/roles/{$role->id}", [
        'display_name' => 'Content Editor',
        'permission_ids' => [],
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'log_type' => 'admin',
        'event' => 'role_updated',
        'causer_id' => $this->admin->id,
    ]);

    $this->actingAs($this->admin)->deleteJson("/admin/roles/{$role->id}");

    $this->assertDatabaseHas('activity_logs', [
        'log_type' => 'admin',
        'event' => 'role_deleted',
        'causer_id' => $this->admin->id,
    ]);
});

test('creating, updating role, and deleting a user logs admin events', function () {
    $userRole = Role::where('name', 'user')->firstOrFail();
    $adminRole = Role::where('name', 'admin')->firstOrFail();

    $response = $this->actingAs($this->admin)->postJson('/admin/users', [
        'name' => 'New User',
        'email' => 'new-user@example.com',
        'password' => 'New-Password1',
        'password_confirmation' => 'New-Password1',
        'role_id' => $userRole->id,
        'is_active' => true,
    ]);

    $userId = $response->json('id');

    $this->assertDatabaseHas('activity_logs', [
        'log_type' => 'admin',
        'event' => 'user_created',
        'causer_id' => $this->admin->id,
    ]);

    $this->actingAs($this->admin)->putJson("/admin/users/{$userId}", [
        'name' => 'New User',
        'email' => 'new-user@example.com',
        'password' => '',
        'password_confirmation' => '',
        'role_id' => $adminRole->id,
        'is_active' => true,
    ]);

    $this->assertDatabaseHas('activity_logs', [
        'log_type' => 'admin',
        'event' => 'user_updated',
        'causer_id' => $this->admin->id,
        'message' => 'Changed role of "new-user@example.com" from User to Administrator.',
    ]);

    $updateLog = ActivityLog::where('event', 'user_updated')->firstOrFail();
    expect($updateLog->properties['before']['role'])->toBe('User')
        ->and($updateLog->properties['after']['role'])->toBe('Administrator')
        ->and($updateLog->properties['after']['password_changed'])->toBeFalse()
        ->and(json_encode($updateLog->properties))->not->toContain('New-Password1');

    $this->actingAs($this->admin)->postJson("/admin/users/{$userId}/disable-two-factor");

    $this->assertDatabaseHas('activity_logs', [
        'log_type' => 'admin',
        'event' => 'user_two_factor_force_disabled',
        'causer_id' => $this->admin->id,
    ]);

    $this->actingAs($this->admin)->deleteJson("/admin/users/{$userId}");

    $this->assertDatabaseHas('activity_logs', [
        'log_type' => 'admin',
        'event' => 'user_deleted',
        'causer_id' => $this->admin->id,
    ]);
});

test('starting and stopping impersonation logs admin events attributed to the admin', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($this->admin)->post("/admin/users/{$user->id}/impersonate");

    $this->assertDatabaseHas('activity_logs', [
        'log_type' => 'admin',
        'event' => 'impersonation_started',
        'causer_id' => $this->admin->id,
    ]);

    $this->post('/impersonate/stop');

    $this->assertDatabaseHas('activity_logs', [
        'log_type' => 'admin',
        'event' => 'impersonation_stopped',
        'causer_id' => $this->admin->id,
    ]);
});

test('clearing logs older than 7 days deletes only matching entries and records the action', function () {
    ActivityLog::factory()->create(['created_at' => now()->subDays(10)]);
    ActivityLog::factory()->create(['created_at' => now()->subDays(1)]);

    $response = $this->actingAs($this->admin)->deleteJson('/admin/activity-log', ['scope' => '7_days']);

    $response->assertOk();
    $response->assertJsonFragment(['deleted' => 1]);

    $this->assertDatabaseCount('activity_logs', 2); // the recent entry + the new "logs cleared" entry
    $this->assertDatabaseHas('activity_logs', [
        'log_type' => 'admin',
        'event' => 'logs_cleared',
        'causer_id' => $this->admin->id,
    ]);
});

test('clearing all logs removes every existing entry', function () {
    ActivityLog::factory()->count(5)->create();

    $response = $this->actingAs($this->admin)->deleteJson('/admin/activity-log', ['scope' => 'all']);

    $response->assertOk();
    $response->assertJsonFragment(['deleted' => 5]);

    $this->assertDatabaseCount('activity_logs', 1); // only the new "logs cleared" entry remains
});

test('clearing logs requires the activity-log.clear permission', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)->deleteJson('/admin/activity-log', ['scope' => 'all'])->assertForbidden();
});
