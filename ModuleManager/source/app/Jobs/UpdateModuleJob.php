<?php

namespace Modules\ModuleManager\Jobs;

use Illuminate\Support\Facades\Artisan;
use Modules\ModuleManager\Enums\ModuleManagerActivityEvent;
use Modules\ModuleManager\Models\ModuleOperation;
use Modules\ModuleManager\Support\ActivityLogger;
use RuntimeException;
use Throwable;

class UpdateModuleJob extends ModuleOperationJob
{
    protected function perform(ModuleOperation $operation): array
    {
        // Always --force: UpdateCommand otherwise calls $this->confirm(),
        // which has no TTY to read from here. The web UI's own confirm
        // dialog — which carries the same "this overwrites local edits"
        // warning — is the confirmation step for this job.
        $exitCode = Artisan::call('wv:update', [
            'modules' => [$operation->module_key],
            '--force' => true,
        ]);

        $output = Artisan::output();

        if ($exitCode !== 0) {
            throw new RuntimeException("wv:update exited with code {$exitCode}.\n\n{$output}");
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
            ModuleManagerActivityEvent::ModuleUpdated->value,
            "Updated the \"{$this->moduleName($operation->module_key)}\" module from {$operation->from_version} to {$operation->to_version}.",
            $operation->causer,
            properties: ['module' => $operation->module_key, 'from_version' => $operation->from_version, 'to_version' => $operation->to_version],
        );
    }

    protected function logFailure(ModuleOperation $operation, Throwable $e): void
    {
        ActivityLogger::record(
            'modules',
            ModuleManagerActivityEvent::ModuleUpdateFailed->value,
            "Failed to update the \"{$this->moduleName($operation->module_key)}\" module: {$e->getMessage()}",
            $operation->causer,
            properties: ['module' => $operation->module_key, 'error' => $e->getMessage()],
        );
    }
}
