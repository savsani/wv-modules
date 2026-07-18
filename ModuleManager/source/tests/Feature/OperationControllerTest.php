<?php

use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Modules\Auth\Database\Seeders\RoleSeeder;
use Modules\ModuleManager\Database\Seeders\ModuleManagerPermissionSeeder;
use Modules\ModuleManager\Enums\ModuleOperationAction;
use Modules\ModuleManager\Enums\ModuleOperationStatus;
use Modules\ModuleManager\Jobs\InstallModuleJob;
use Modules\ModuleManager\Jobs\RunPendingMigrationsJob;
use Modules\ModuleManager\Jobs\UpdateModuleJob;
use Modules\ModuleManager\Models\ModuleOperation;

beforeEach(function () {
    $this->seed([ModuleManagerPermissionSeeder::class, RoleSeeder::class]);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    // Every module the real registry knows about (Core, Auth, Admin,
    // ActivityLog) is already on disk in this workspace, so a registry
    // entry that's guaranteed "not installed" needs a throwaway target
    // that never exists — added directly to config rather than to disk.
    config(['wv-modules.modules.faketestmodule' => [
        'name' => 'FakeTestModule',
        'description' => 'A registry entry that exists only for this test — never actually installed.',
        'depends_on' => [],
        'path' => 'FakeTestModule',
        'target' => 'Modules/FakeTestModule',
        'npm' => null,
    ]]);

    Queue::fake();
});

test('an unknown module key 404s for every action', function () {
    $this->actingAs($this->admin)->postJson('/admin/module-manager/not-a-real-module/install')->assertNotFound();
    $this->actingAs($this->admin)->postJson('/admin/module-manager/not-a-real-module/update')->assertNotFound();
    $this->actingAs($this->admin)->postJson('/admin/module-manager/not-a-real-module/migrate')->assertNotFound();

    Queue::assertNothingPushed();
});

test('installing an already-installed module is rejected', function () {
    // Core is already present in Modules/Core in this repo.
    $this->actingAs($this->admin)->postJson('/admin/module-manager/core/install')
        ->assertStatus(422);

    Queue::assertNothingPushed();
});

test('installing a not-yet-installed module queues an InstallModuleJob', function () {
    $response = $this->actingAs($this->admin)->postJson('/admin/module-manager/faketestmodule/install');

    $response->assertCreated();
    $response->assertJsonFragment(['module_key' => 'faketestmodule', 'action' => 'install', 'status' => 'pending']);

    $operation = ModuleOperation::first();
    expect($operation->module_key)->toBe('faketestmodule')
        ->and($operation->action)->toBe(ModuleOperationAction::Install)
        ->and($operation->status)->toBe(ModuleOperationStatus::Pending)
        ->and($operation->causer_id)->toBe($this->admin->id);

    Queue::assertPushed(InstallModuleJob::class, fn ($job) => $job->moduleOperationId === $operation->id);
});

test('updating a not-yet-installed module is rejected', function () {
    $this->actingAs($this->admin)->postJson('/admin/module-manager/faketestmodule/update')
        ->assertStatus(422);

    Queue::assertNothingPushed();
});

test('updating an installed module queues an UpdateModuleJob with the current version recorded', function () {
    $response = $this->actingAs($this->admin)->postJson('/admin/module-manager/core/update');

    $response->assertCreated();
    $response->assertJsonFragment(['module_key' => 'core', 'action' => 'update', 'status' => 'pending']);

    $operation = ModuleOperation::first();
    expect($operation->module_key)->toBe('core')
        ->and($operation->action)->toBe(ModuleOperationAction::Update)
        ->and($operation->from_version)->not->toBeNull();

    Queue::assertPushed(UpdateModuleJob::class, fn ($job) => $job->moduleOperationId === $operation->id);
});

test('migrating queues a RunPendingMigrationsJob', function () {
    $response = $this->actingAs($this->admin)->postJson('/admin/module-manager/core/migrate');

    $response->assertCreated();
    $response->assertJsonFragment(['module_key' => 'core', 'action' => 'migrate', 'status' => 'pending']);

    $operation = ModuleOperation::first();
    Queue::assertPushed(RunPendingMigrationsJob::class, fn ($job) => $job->moduleOperationId === $operation->id);
});

test('non-admins cannot trigger any module operation', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)->postJson('/admin/module-manager/core/update')->assertForbidden();

    Queue::assertNothingPushed();
});

test('the status endpoint reflects the operation record', function () {
    $operation = ModuleOperation::factory()->succeeded()->create(['causer_id' => $this->admin->id]);

    $response = $this->actingAs($this->admin)->getJson("/admin/module-manager/operations/{$operation->id}");

    $response->assertOk();
    $response->assertJsonFragment(['id' => $operation->id, 'status' => 'succeeded']);
});
