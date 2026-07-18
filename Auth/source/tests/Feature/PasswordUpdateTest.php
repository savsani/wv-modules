<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('password can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->put('/user/password', [
            'current_password' => 'password',
            'password' => 'New-Password1',
            'password_confirmation' => 'New-Password1',
        ]);

    $response->assertSessionHasNoErrors();

    $this->assertTrue(Hash::check('New-Password1', $user->fresh()->password));
});

test('correct current password must be provided to update password', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->put('/user/password', [
            'current_password' => 'wrong-password',
            'password' => 'New-Password1',
            'password_confirmation' => 'New-Password1',
        ]);

    $response->assertSessionHasErrorsIn('updatePassword', 'current_password');
});
