<?php

namespace Modules\ModuleManager\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Modules\ModuleManager\Enums\ModuleManagerActivityEvent;
use Modules\ModuleManager\Enums\ModuleOperationStatus;
use Modules\ModuleManager\Models\ModuleOperation;
use Modules\ModuleManager\Support\ActivityLogger;
use Throwable;
use Wv\ModuleInstallerKit\ModuleRegistry;

/**
 * Shared skeleton for install/update/migrate jobs: acquire the single
 * global module-manager lock (wv:install/wv:update read-modify-write
 * composer.json/package.json — two concurrent runs could corrupt them),
 * flip the ModuleOperation through running -> succeeded/failed, and always
 * fire an activity event either way. Subclasses only supply the actual
 * artisan call and the log wording.
 */
abstract class ModuleOperationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public function __construct(public int $moduleOperationId)
    {
        // Queueable declares an untyped $queue property — set it here
        // instead of redeclaring it with a type, which PHP treats as an
        // incompatible property definition and fatals at class-load time.
        $this->onQueue('modules');
    }

    public function handle(): void
    {
        $operation = ModuleOperation::findOrFail($this->moduleOperationId);

        $lock = Cache::lock('modules:manager:lock', 300);

        if (! $lock->get()) {
            $operation->update([
                'status' => ModuleOperationStatus::Failed,
                'error_message' => 'Another module operation was already in progress. Try again shortly.',
                'finished_at' => now(),
            ]);

            ActivityLogger::record(
                'modules',
                ModuleManagerActivityEvent::ModuleOperationBlocked->value,
                "Skipped a module operation on \"{$operation->module_key}\" — another one was already running.",
                $operation->causer,
            );

            return;
        }

        $operation->update(['status' => ModuleOperationStatus::Running, 'started_at' => now()]);

        try {
            $result = $this->perform($operation);

            $operation->update([
                'status' => ModuleOperationStatus::Succeeded,
                'to_version' => $result['to_version'] ?? $operation->to_version,
                'output' => $result['output'] ?? null,
                'finished_at' => now(),
            ]);

            $this->logSuccess($operation->refresh());
        } catch (Throwable $e) {
            $operation->update([
                'status' => ModuleOperationStatus::Failed,
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            $this->logFailure($operation->refresh(), $e);
        } finally {
            $lock->release();
        }
    }

    /**
     * @return array{to_version?: ?string, output?: ?string}
     */
    abstract protected function perform(ModuleOperation $operation): array;

    abstract protected function logSuccess(ModuleOperation $operation): void;

    abstract protected function logFailure(ModuleOperation $operation, Throwable $e): void;

    /**
     * Read the version an install/update just left behind, straight off
     * the module's own .wv-manifest.json (the same file ManifestWriter
     * writes) — the source of truth, rather than trusting artisan output.
     */
    protected function installedVersion(string $moduleKey): ?string
    {
        $module = app(ModuleRegistry::class)->find($moduleKey);

        if (! $module) {
            return null;
        }

        $manifestPath = base_path($module['target'].'/.wv-manifest.json');
        $files = app(Filesystem::class);

        if (! $files->exists($manifestPath)) {
            return null;
        }

        return json_decode($files->get($manifestPath), true)['version'] ?? null;
    }

    protected function moduleName(string $moduleKey): string
    {
        return app(ModuleRegistry::class)->find($moduleKey)['name'] ?? $moduleKey;
    }
}
