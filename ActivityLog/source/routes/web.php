<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Modules\ActivityLog\Http\Controllers\ActivityLogController;

Route::middleware(array_filter([
    'auth',
    Features::enabled(Features::emailVerification()) ? 'verified' : null,
]))->group(function () {
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/activity-log', [ActivityLogController::class, 'index'])
            ->middleware('permission:activity-log.view')
            ->name('activity-log.index');
        Route::delete('/activity-log', [ActivityLogController::class, 'clear'])
            ->middleware('permission:activity-log.clear')
            ->name('activity-log.clear');
    });
});
