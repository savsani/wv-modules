<?php

namespace Modules\ModuleManager\Jobs;

use Illuminate\Support\Facades\Artisan;
use Modules\ModuleManager\Enums\ModuleManagerActivityEvent;
use Modules\ModuleManager\Models\ModuleOperation;
use Modules\ModuleManager\Support\ActivityLogger;
use RuntimeException;
use Throwable;

class InstallModuleJob extends ModuleOperationJob
{
    protected function perform(ModuleOperation $operation): array
    {
        $exitCode = Artisan::call('wv:install', [
            'modules' => [$operation->module_key],
        ]);

        $output = Artisan::output();

        if ($exitCode !== 0) {
            throw new RuntimeException("wv:install exited with code {$exitCode}.\n\n{$output}");
        }

        return [
            'to_version' => $this->installedVersion($operation->module_key),
            'output' => $output,
        ];
    }

    protected function logSuccess(ModuleOperation $operation): void
    {
        ActivityLogger::record(
            'modules',
            ModuleManagerActivityEvent::ModuleInstalled->value,
            "Installed the \"{$this->moduleName($operation->module_key)}\" module (version {$operation->to_version}).",
            $operation->causer,
            properties: ['module' => $operation->module_key, 'to_version' => $operation->to_version],
        );
    }

    protected function logFailure(ModuleOperation $operation, Throwable $e): void
    {
        ActivityLogger::record(
            'modules',
            ModuleManagerActivityEvent::ModuleInstallFailed->value,
            "Failed to install the \"{$this->moduleName($operation->module_key)}\" module: {$e->getMessage()}",
            $operation->causer,
            properties: ['module' => $operation->module_key, 'error' => $e->getMessage()],
        );
    }
}
