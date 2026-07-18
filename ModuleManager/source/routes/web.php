<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Modules\ModuleManager\Http\Controllers\ModuleManagerController;
use Modules\ModuleManager\Http\Controllers\OperationController;

Route::middleware(array_filter([
    'auth',
    Features::enabled(Features::emailVerification()) ? 'verified' : null,
]))->group(function () {
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/module-manager', [ModuleManagerController::class, 'index'])
            ->middleware('permission:modules.view')
            ->name('module-manager.index');

        Route::get('/module-manager/operations/{operation}', [OperationController::class, 'show'])
            ->middleware('permission:modules.view')
            ->name('module-manager.operations.show');

        Route::post('/module-manager/{module}/install', [OperationController::class, 'install'])
            ->middleware('permission:modules.install')
            ->name('module-manager.install');

        Route::post('/module-manager/{module}/update', [OperationController::class, 'update'])
            ->middleware('permission:modules.update')
            ->name('module-manager.update');

        Route::post('/module-manager/{module}/migrate', [OperationController::class, 'migrate'])
            ->middleware('permission:modules.migrate')
            ->name('module-manager.migrate');
    });
});
