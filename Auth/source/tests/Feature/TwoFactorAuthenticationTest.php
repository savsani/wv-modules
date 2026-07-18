<?php

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Laravel\Fortify\Fortify;

test('two factor authentication routes require password confirmation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/user/two-factor-authentication');

    $response->assertRedirect(route('password.confirm'));
});

test('two factor authentication can be enabled', function () {
    $user = User::factory()->create();
    confirmPassword();

    $this->actingAs($user)->post('/user/two-factor-authentication');

    expect($user->fresh()->two_factor_secret)->not->toBeNull();
});

test('two factor authentication can be confirmed with a valid code', function () {
    $user = User::factory()->create();
    confirmPassword();

    $this->actingAs($user)->post('/user/two-factor-authentication');

    $code = currentOtpFor($user);

    $response = $this->actingAs($user)->post('/user/confirmed-two-factor-authentication', [
        'code' => $code,
    ]);

    $response->assertSessionHasNoErrors();
    expect($user->fresh()->two_factor_confirmed_at)->not->toBeNull();
});

test('two factor authentication confirmation fails with an invalid code', function () {
    $user = User::factory()->create();
    confirmPassword();

    $this->actingAs($user)->post('/user/two-factor-authentication');

    $response = $this->actingAs($user)->post('/user/confirmed-two-factor-authentication', [
        'code' => '000000',
    ]);

    $response->assertSessionHasErrors();
    expect($user->fresh()->two_factor_confirmed_at)->toBeNull();
});

test('recovery codes can be viewed once two factor authentication is confirmed', function () {
    $user = User::factory()->create();
    confirmPassword();

    $this->actingAs($user)->post('/user/two-factor-authentication');
    $this->actingAs($user)->post('/user/confirmed-two-factor-authentication', [
        'code' => currentOtpFor($user),
    ]);

    $response = $this->actingAs($user)->get('/user/two-factor-recovery-codes');

    $response->assertStatus(200);
    $response->assertJsonCount(8);
});

test('login redirects to the two factor challenge when enabled', function () {
    $user = User::factory()->create();
    confirmPassword();

    $this->actingAs($user)->post('/user/two-factor-authentication');
    $this->actingAs($user)->post('/user/confirmed-two-factor-authentication', [
        'code' => currentOtpFor($user),
    ]);

    auth()->logout();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertRedirect(route('two-factor.login'));
});

test('two factor challenge completes login with a valid code', function () {
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

    // The confirmation step above already consumed this 30-second TOTP
    // window's code in Fortify's anti-replay cache; since Google2FA keys
    // off real wall-clock time (not Carbon's testing clock), flush the
    // cache so the same code can be verified again for the login challenge.
    Cache::flush();

    $response = $this->post('/two-factor-challenge', [
        'code' => currentOtpFor($user),
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('disabling an unfinished two factor setup flashes a setup cancelled status', function () {
    $user = User::factory()->create();
    confirmPassword();

    $this->actingAs($user)->post('/user/two-factor-authentication');

    $response = $this->actingAs($user)->delete('/user/two-factor-authentication', [
        'cancelled' => true,
    ]);

    $response->assertSessionHas('status', 'two-factor-authentication-setup-cancelled');
    expect($user->fresh()->two_factor_secret)->toBeNull();
});

test('disabling a confirmed two factor setup flashes a disabled status', function () {
    $user = User::factory()->create();
    confirmPassword();

    $this->actingAs($user)->post('/user/two-factor-authentication');
    $this->actingAs($user)->post('/user/confirmed-two-factor-authentication', [
        'code' => currentOtpFor($user),
    ]);

    $response = $this->actingAs($user)->delete('/user/two-factor-authentication');

    $response->assertSessionHas('status', Fortify::TWO_FACTOR_AUTHENTICATION_DISABLED);
    expect($user->fresh()->two_factor_confirmed_at)->toBeNull();
});

test('logging out discards an unfinished two factor setup', function () {
    $user = User::factory()->create();
    confirmPassword();

    $this->actingAs($user)->post('/user/two-factor-authentication');

    expect($user->fresh()->two_factor_secret)->not->toBeNull();

    $this->post('/logout');

    expect($user->fresh())
        ->two_factor_secret->toBeNull()
        ->two_factor_recovery_codes->toBeNull()
        ->two_factor_confirmed_at->toBeNull();
});

test('logging out does not affect a confirmed two factor setup', function () {
    $user = User::factory()->create();
    confirmPassword();

    $this->actingAs($user)->post('/user/two-factor-authentication');
    $this->actingAs($user)->post('/user/confirmed-two-factor-authentication', [
        'code' => currentOtpFor($user),
    ]);

    $this->post('/logout');

    expect($user->fresh())
        ->two_factor_secret->not->toBeNull()
        ->two_factor_confirmed_at->not->toBeNull();
});

test('two factor challenge completes login with a valid recovery code', function () {
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

    $response = $this->post('/two-factor-challenge', [
        'recovery_code' => $recoveryCode,
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false));
});
