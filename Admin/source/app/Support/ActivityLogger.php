<?php

namespace Modules\Admin\Support;

use App\Models\User;

/**
 * Thin dispatcher kept for call-site ergonomics — fires the
 * 'activity.recorded' event, Laravel's own event bus, so this module
 * never needs to know whether Modules/ActivityLog (or anything else) is
 * listening. This class is intentionally duplicated (not shared) in each
 * module that logs activity — see Modules\Auth\Support\ActivityLogger —
 * so no module ever depends on another module's class for this.
 */
class ActivityLogger
{
    /**
     * @param  array<string, mixed>|null  $properties
     */
    public static function record(
        string $type,
        string $event,
        string $message,
        ?User $causer = null,
        ?string $causerEmail = null,
        ?array $properties = null,
    ): void {
        event('activity.recorded', [[
            'type' => $type,
            'event' => $event,
            'message' => $message,
            'causer' => $causer,
            'causer_email' => $causerEmail,
            'properties' => $properties,
        ]]);
    }
}
