<?php

namespace Modules\ModuleManager\Jobs;

use Illuminate\Support\Facades\Artisan;
use Modules\ModuleManager\Enums\ModuleManagerActivityEvent;
use Modules\ModuleManager\Models\ModuleOperation;
use Modules\ModuleManager\Support\ActivityLogger;
use RuntimeException;
use Throwable;

class RunPendingMigrationsJob extends ModuleOperationJob
{
    protected function perform(ModuleOperation $operation): array
    {
        $exitCode = Artisan::call('migrate', ['--force' => true]);

        $output = Artisan::output();

        if ($exitCode !== 0) {
            throw new RuntimeException("migrate exited with code {$exitCode}.\n\n{$output}");
        }

        return ['output' => $output];
    }

    protected function logSuccess(ModuleOperation $operation): void
    {
        ActivityLogger::record(
            'modules',
            ModuleManagerActivityEvent::ModuleMigrationsRun->value,
            "Ran pending migrations after changes to the \"{$this->moduleName($operation->module_key)}\" module.",
            $operation->causer,
            properties: ['module' => $operation->module_key],
        );
    }

    protected function logFailure(ModuleOperation $operation, Throwable $e): void
    {
        ActivityLogger::record(
            'modules',
            ModuleManagerActivityEvent::ModuleMigrationsFailed->value,
            "Failed to run migrations after changes to the \"{$this->moduleName($operation->module_key)}\" module: {$e->getMessage()}",
            $operation->causer,
            properties: ['module' => $operation->module_key, 'error' => $e->getMessage()],
        );
    }
}
