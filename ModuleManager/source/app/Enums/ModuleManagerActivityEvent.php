<?php

namespace Modules\ModuleManager\Enums;

/**
 * Event keys for module install/update/migrate activity — used only for
 * type-safe call sites when dispatching the 'activity.recorded' event (see
 * Modules\ModuleManager\Support\ActivityLogger). Whatever's listening for
 * that event (e.g. Modules/ActivityLog) renders these labels generically via
 * Str::headline() on the raw string — nothing needs to know this enum
 * exists, so there's no catalog to keep in sync.
 */
enum ModuleManagerActivityEvent: string
{
    case ModuleInstalled = 'module_installed';
    case ModuleInstallFailed = 'module_install_failed';
    case ModuleUpdated = 'module_updated';
    case ModuleUpdateFailed = 'module_update_failed';
    case ModuleMigrationsRun = 'module_migrations_run';
    case ModuleMigrationsFailed = 'module_migrations_failed';
    case ModuleOperationBlocked = 'module_operation_blocked';
}
