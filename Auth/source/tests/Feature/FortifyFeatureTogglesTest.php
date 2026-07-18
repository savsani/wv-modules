<?php

use Laravel\Fortify\Features;

function fortifyFeaturesFor(array $env): array
{
    $originals = [];

    foreach ($env as $key => $value) {
        $originals[$key] = getenv($key);

        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    $features = (static fn () => require config_path('fortify.php'))()['features'];

    foreach ($originals as $key => $original) {
        if ($original === false) {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        } else {
            putenv("{$key}={$original}");
            $_ENV[$key] = $original;
            $_SERVER[$key] = $original;
        }
    }

    return $features;
}

test('signup mode none disables registration and email verification', function () {
    $features = fortifyFeaturesFor(['AUTH_SIGNUP_MODE' => 'none']);

    expect($features)->not->toContain(Features::registration());
    expect($features)->not->toContain(Features::emailVerification());
});

test('signup mode full enables registration without email verification', function () {
    $features = fortifyFeaturesFor(['AUTH_SIGNUP_MODE' => 'full']);

    expect($features)->toContain(Features::registration());
    expect($features)->not->toContain(Features::emailVerification());
});

test('signup mode full_verification enables registration and email verification', function () {
    $features = fortifyFeaturesFor(['AUTH_SIGNUP_MODE' => 'full_verification']);

    expect($features)->toContain(Features::registration());
    expect($features)->toContain(Features::emailVerification());
});

test('forgot password toggle controls reset passwords feature', function (string $value, bool $enabled) {
    $features = fortifyFeaturesFor(['AUTH_FORGOT_PASSWORD_ENABLED' => $value]);

    expect(in_array(Features::resetPasswords(), $features))->toBe($enabled);
})->with([
    ['true', true],
    ['false', false],
]);

test('update profile info toggle controls update profile information feature', function (string $value, bool $enabled) {
    $features = fortifyFeaturesFor(['AUTH_UPDATE_PROFILE_INFO_ENABLED' => $value]);

    expect(in_array(Features::updateProfileInformation(), $features))->toBe($enabled);
})->with([
    ['true', true],
    ['false', false],
]);

test('update password toggle controls update passwords feature', function (string $value, bool $enabled) {
    $features = fortifyFeaturesFor(['AUTH_UPDATE_PASSWORD_ENABLED' => $value]);

    expect(in_array(Features::updatePasswords(), $features))->toBe($enabled);
})->with([
    ['true', true],
    ['false', false],
]);

test('2fa toggle controls two factor authentication feature', function (string $value, bool $enabled) {
    $features = fortifyFeaturesFor(['AUTH_2FA_ENABLED' => $value]);

    expect(in_array(Features::twoFactorAuthentication(), $features))->toBe($enabled);
})->with([
    ['true', true],
    ['false', false],
]);
