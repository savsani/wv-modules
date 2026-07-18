<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Modules\Auth\Http\Controllers\DashboardController;

// laravel/fortify auto-registers its own auth routes (login, register,
// password reset, two-factor, email verification) based on
// config/fortify.php — this module only wires the action/view bindings
// that back those routes (see FortifyServiceProvider).

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware(array_filter([
    'auth',
    Features::enabled(Features::emailVerification()) ? 'verified' : null,
]))->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/profile', function () {
        return view('profile.show');
    })->name('profile.show');
});
