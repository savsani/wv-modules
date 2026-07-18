<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Modules\Admin\Http\Controllers\Permissions\PermissionController;
use Modules\Admin\Http\Controllers\Roles\RoleController;
use Modules\Admin\Http\Controllers\Users\ImpersonateController;
use Modules\Admin\Http\Controllers\Users\UserController;

Route::middleware(array_filter([
    'auth',
    Features::enabled(Features::emailVerification()) ? 'verified' : null,
]))->group(function () {
    // Outside the `role:admin` group: while impersonating, the authenticated
    // user is the impersonated target, who may not hold the admin role.
    Route::post('/impersonate/stop', [ImpersonateController::class, 'stop'])
        ->name('admin.impersonate.stop');

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/permissions', [PermissionController::class, 'index'])
            ->middleware('permission:permissions.view')
            ->name('permissions.index');

        Route::get('/roles', [RoleController::class, 'index'])
            ->middleware('permission:roles.view')
            ->name('roles.index');
        Route::post('/roles', [RoleController::class, 'store'])
            ->middleware('permission:roles.create')
            ->name('roles.store');
        Route::put('/roles/{role}', [RoleController::class, 'update'])
            ->middleware('permission:roles.edit')
            ->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
            ->middleware('permission:roles.delete')
            ->name('roles.destroy');

        Route::get('/users', [UserController::class, 'index'])
            ->middleware('permission:users.view')
            ->name('users.index');
        Route::post('/users', [UserController::class, 'store'])
            ->middleware('permission:users.create')
            ->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])
            ->middleware('permission:users.edit')
            ->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])
            ->middleware('permission:users.delete')
            ->name('users.destroy');
        Route::post('/users/{user}/disable-two-factor', [UserController::class, 'disableTwoFactor'])
            ->middleware('permission:users.disable_2fa')
            ->name('users.disable-two-factor');
        Route::post('/users/{user}/impersonate', [ImpersonateController::class, 'take'])
            ->middleware('permission:users.impersonate')
            ->name('users.impersonate');
    });
});
