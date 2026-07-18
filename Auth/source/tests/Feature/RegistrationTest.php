<?php

use App\Models\User;
use Laravel\Fortify\Features;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'New-Password1',
        'password_confirmation' => 'New-Password1',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
    ]);
});

test('registration requires name, email, and password', function () {
    $response = $this->post('/register', []);

    $response->assertSessionHasErrors(['name', 'email', 'password']);
});

test('registration requires a unique email', function () {
    User::factory()->create(['email' => 'test@example.com']);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'New-Password1',
        'password_confirmation' => 'New-Password1',
    ]);

    $response->assertSessionHasErrors('email');
});

test('registering without the email verification feature enabled does not attempt to send a verification email', function () {
    config(['fortify.features' => array_filter([
        Features::registration(),
    ])]);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'unverified-mode@example.com',
        'password' => 'New-Password1',
        'password_confirmation' => 'New-Password1',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});
