<?php

namespace Modules\ModuleManager\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ModuleManager\Enums\ModuleOperationAction;
use Modules\ModuleManager\Enums\ModuleOperationStatus;
use Modules\ModuleManager\Jobs\InstallModuleJob;
use Modules\ModuleManager\Jobs\RunPendingMigrationsJob;
use Modules\ModuleManager\Jobs\UpdateModuleJob;
use Modules\ModuleManager\Models\ModuleOperation;
use Wv\ModuleInstallerKit\ModuleRegistry;

class OperationController extends Controller
{
    /**
     * Queue an install for a module that isn't on disk yet. The module key
     * is only ever accepted if it resolves against the registry — never
     * passed straight through to the underlying artisan command.
     */
    public function install(Request $request, string $module, ModuleRegistry $registry, Filesystem $files): JsonResponse
    {
        $definition = $registry->find($module);

        abort_unless($definition !== null, 404);
        abort_if($files->isDirectory(base_path($definition['target'])), 422, "{$definition['name']} is already installed.");

        $operation = ModuleOperation::create([
            'module_key' => $module,
            'action' => ModuleOperationAction::Install,
            'status' => ModuleOperationStatus::Pending,
            'causer_id' => $request->user()->id,
        ]);

        InstallModuleJob::dispatch($operation->id);

        return response()->json($this->transform($operation), 201);
    }

    /**
     * Queue an update for an already-installed module. Always overwrites
     * the module directory wholesale — the confirm dialog on the frontend
     * carries that warning before this ever fires.
     */
    public function update(Request $request, string $module, ModuleRegistry $registry, Filesystem $files): JsonResponse
    {
        $definition = $registry->find($module);

        abort_unless($definition !== null, 404);

        $target = base_path($definition['target']);
        abort_unless($files->isDirectory($target), 422, "{$definition['name']} isn't installed yet.");

        $manifestPath = $target.'/.wv-manifest.json';
        $currentVersion = $files->exists($manifestPath)
            ? json_decode($files->get($manifestPath), true)['version'] ?? null
            : null;

        $operation = ModuleOperation::create([
            'module_key' => $module,
            'action' => ModuleOperationAction::Update,
            'status' => ModuleOperationStatus::Pending,
            'from_version' => $currentVersion,
            'causer_id' => $request->user()->id,
        ]);

        UpdateModuleJob::dispatch($operation->id);

        return response()->json($this->transform($operation), 201);
    }

    /**
     * Queue a `migrate --force` run — kept as its own deliberate, separately
     * permissioned action rather than folded silently into install/update,
     * since schema changes are the highest blast-radius step here.
     */
    public function migrate(Request $request, string $module, ModuleRegistry $registry): JsonResponse
    {
        abort_unless($registry->find($module) !== null, 404);

        $operation = ModuleOperation::create([
            'module_key' => $module,
            'action' => ModuleOperationAction::Migrate,
            'status' => ModuleOperationStatus::Pending,
            'causer_id' => $request->user()->id,
        ]);

        RunPendingMigrationsJob::dispatch($operation->id);

        return response()->json($this->transform($operation), 201);
    }

    /**
     * Polled by the frontend until the operation reaches a terminal status.
     */
    public function show(ModuleOperation $operation): JsonResponse
    {
        return response()->json($this->transform($operation));
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(ModuleOperation $operation): array
    {
        return [
            'id' => $operation->id,
            'module_key' => $operation->module_key,
            'action' => $operation->action->value,
            'status' => $operation->status->value,
            'from_version' => $operation->from_version,
            'to_version' => $operation->to_version,
            'output' => $operation->output,
            'error_message' => $operation->error_message,
        ];
    }
}
