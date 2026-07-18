<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Modules\Auth\Database\Seeders\RoleSeeder;
use Modules\ModuleManager\Database\Seeders\ModuleManagerPermissionSeeder;
use Modules\ModuleManager\Enums\ModuleOperationAction;
use Modules\ModuleManager\Enums\ModuleOperationStatus;
use Modules\ModuleManager\Jobs\InstallModuleJob;
use Modules\ModuleManager\Jobs\RunPendingMigrationsJob;
use Modules\ModuleManager\Models\ModuleOperation;

beforeEach(function () {
    $this->seed([ModuleManagerPermissionSeeder::class, RoleSeeder::class]);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('a successful install marks the operation succeeded and logs an activity event', function () {
    Artisan::shouldReceive('call')
        ->once()
        ->with('wv:install', ['modules' => ['core']])
        ->andReturn(0);
    Artisan::shouldReceive('output')->once()->andReturn('Installed: Core');

    $operation = ModuleOperation::create([
        'module_key' => 'core',
        'action' => ModuleOperationAction::Install,
        'status' => ModuleOperationStatus::Pending,
        'causer_id' => $this->admin->id,
    ]);

    (new InstallModuleJob($operation->id))->handle();

    $operation->refresh();
    expect($operation->status)->toBe(ModuleOperationStatus::Succeeded)
        ->and($operation->output)->toBe('Installed: Core')
        ->and($operation->to_version)->not->toBeNull()
        ->and($operation->finished_at)->not->toBeNull();

    $this->assertDatabaseHas('activity_logs', [
        'log_type' => 'modules',
        'event' => 'module_installed',
        'causer_id' => $this->admin->id,
    ]);
});

test('a failed install marks the operation failed and logs an activity event', function () {
    Artisan::shouldReceive('call')->once()->andReturn(1);
    Artisan::shouldReceive('output')->once()->andReturn('boom');

    $operation = ModuleOperation::create([
        'module_key' => 'core',
        'action' => ModuleOperationAction::Install,
        'status' => ModuleOperationStatus::Pending,
        'causer_id' => $this->admin->id,
    ]);

    (new InstallModuleJob($operation->id))->handle();

    $operation->refresh();
    expect($operation->status)->toBe(ModuleOperationStatus::Failed)
        ->and($operation->error_message)->toContain('exited with code 1');

    $this->assertDatabaseHas('activity_logs', [
        'log_type' => 'modules',
        'event' => 'module_install_failed',
        'causer_id' => $this->admin->id,
    ]);
});

test('an operation is skipped when another one already holds the lock', function () {
    Artisan::shouldReceive('call')->never();

    Cache::lock('modules:manager:lock', 300)->get();

    $operation = ModuleOperation::create([
        'module_key' => 'core',
        'action' => ModuleOperationAction::Install,
        'status' => ModuleOperationStatus::Pending,
        'causer_id' => $this->admin->id,
    ]);

    (new InstallModuleJob($operation->id))->handle();

    $operation->refresh();
    expect($operation->status)->toBe(ModuleOperationStatus::Failed)
        ->and($operation->error_message)->toContain('already in progress');

    $this->assertDatabaseHas('activity_logs', [
        'log_type' => 'modules',
        'event' => 'module_operation_blocked',
    ]);
});

test('running pending migrations succeeds against the real migrator and logs an activity event', function () {
    $operation = ModuleOperation::create([
        'module_key' => 'core',
        'action' => ModuleOperationAction::Migrate,
        'status' => ModuleOperationStatus::Pending,
        'causer_id' => $this->admin->id,
    ]);

    (new RunPendingMigrationsJob($operation->id))->handle();

    $operation->refresh();
    expect($operation->status)->toBe(ModuleOperationStatus::Succeeded)
        ->and($operation->output)->not->toBeNull();

    $this->assertDatabaseHas('activity_logs', [
        'log_type' => 'modules',
        'event' => 'module_migrations_run',
        'causer_id' => $this->admin->id,
    ]);
});
