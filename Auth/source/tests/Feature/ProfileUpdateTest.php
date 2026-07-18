<?php

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->put('/user/profile-information', [
            'name' => 'New Name',
            'email' => $user->email,
        ]);

    $response->assertSessionHasNoErrors();

    $user->refresh();

    expect($user->name)->toBe('New Name');
});

test('changing email resets verification and sends a new verification notification', function () {
    Notification::fake();

    $user = User::factory()->create(['email' => 'old@example.com']);

    $this
        ->actingAs($user)
        ->put('/user/profile-information', [
            'name' => $user->name,
            'email' => 'new@example.com',
        ]);

    $user->refresh();

    expect($user->email)->toBe('new@example.com')
        ->and($user->email_verified_at)->toBeNull();

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('keeping the same email does not reset verification', function () {
    $user = User::factory()->create(['email' => 'same@example.com']);

    $this
        ->actingAs($user)
        ->put('/user/profile-information', [
            'name' => 'Updated Name',
            'email' => 'same@example.com',
        ]);

    $user->refresh();

    expect($user->email_verified_at)->not->toBeNull();
});

test('profile information section is hidden when the feature is disabled', function () {
    config(['fortify.features' => array_filter([
        Features::updatePasswords(),
        Features::twoFactorAuthentication(),
    ])]);

    $response = $this->actingAs(User::factory()->create())->get('/profile');

    $response->assertOk();
    $response->assertDontSee('Profile Information');
    $response->assertSee('Update Password');
    $response->assertSee('Two Factor Authentication');
});

test('update password section is hidden when the feature is disabled', function () {
    config(['fortify.features' => array_filter([
        Features::updateProfileInformation(),
        Features::twoFactorAuthentication(),
    ])]);

    $response = $this->actingAs(User::factory()->create())->get('/profile');

    $response->assertOk();
    $response->assertSee('Profile Information');
    $response->assertDontSee('Update Password');
    $response->assertSee('Two Factor Authentication');
});

test('two factor authentication section is hidden when the feature is disabled', function () {
    config(['fortify.features' => array_filter([
        Features::updateProfileInformation(),
        Features::updatePasswords(),
    ])]);

    $response = $this->actingAs(User::factory()->create())->get('/profile');

    $response->assertOk();
    $response->assertSee('Profile Information');
    $response->assertSee('Update Password');
    $response->assertDontSee('Two Factor Authentication');
});
