<?php

namespace Modules\ModuleManager\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\ModuleManager\Enums\ModuleOperationStatus;
use Modules\ModuleManager\Models\ModuleOperation;
use Wv\ModuleInstallerKit\ModuleRegistry;
use Wv\ModuleInstallerKit\Support\ModuleVersionChecker;

class ModuleManagerController extends Controller
{
    /**
     * Display every registered module with its installed/latest version —
     * the web equivalent of `php artisan wv:list`.
     */
    public function index(ModuleRegistry $registry, ModuleVersionChecker $versionChecker, Filesystem $files): View
    {
        $activeOperations = ModuleOperation::whereIn('status', [ModuleOperationStatus::Pending, ModuleOperationStatus::Running])
            ->latest('id')
            ->get()
            ->unique('module_key')
            ->keyBy('module_key');

        $modules = $registry->all()->map(function (array $module) use ($files, $versionChecker, $activeOperations, $registry) {
            $target = base_path($module['target']);
            $manifestPath = $target.'/.wv-manifest.json';
            $isInstalled = $files->isDirectory($target);

            $installedVersion = $files->exists($manifestPath)
                ? json_decode($files->get($manifestPath), true)['version'] ?? 'unknown'
                : null;

            $latestVersion = Cache::remember(
                "wv:latest:{$module['key']}",
                now()->addMinutes(10),
                fn () => $versionChecker->latest($module['repo'], $module['ref'], $module['path']),
            );

            $missingDependencies = collect($module['depends_on'])
                ->reject(fn (string $dependencyKey) => $files->isDirectory(base_path($registry->find($dependencyKey)['target'])))
                ->map(fn (string $dependencyKey) => $registry->find($dependencyKey)['name'])
                ->values();

            $activeOperation = $activeOperations->get($module['key']);

            return [
                // dataTable() (Modules/Core/resources/js/data-table.js) keys its
                // sort/pagination/visibility bookkeeping off `row.id` — modules
                // don't have a numeric id, so alias the registry key as one.
                'id' => $module['key'],
                'key' => $module['key'],
                'name' => $module['name'],
                'description' => $module['description'],
                'depends_on' => $module['depends_on'],
                'missing_dependencies' => $missingDependencies,
                'is_installed' => $isInstalled,
                'installed_version' => $installedVersion,
                'latest_version' => $latestVersion,
                'update_available' => $isInstalled && $latestVersion !== null && $installedVersion !== $latestVersion,
                'active_operation' => $activeOperation ? [
                    'id' => $activeOperation->id,
                    'action' => $activeOperation->action->value,
                    'status' => $activeOperation->status->value,
                ] : null,
            ];
        })->values();

        return view('modulemanager::index', ['modules' => $modules]);
    }
}
