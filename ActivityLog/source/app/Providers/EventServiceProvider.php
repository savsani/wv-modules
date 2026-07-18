<?php

namespace Modules\ActivityLog\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Modules\ActivityLog\Models\ActivityLog;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = false;

    public function boot(): void
    {
        parent::boot();

        // This is the ONLY place an activity_logs row gets written, and
        // the ONLY place in the whole app that knows this module exists —
        // any module (Auth, Admin, or one written years from now) can log
        // an activity by firing this string-named event with no import,
        // no interface, no knowledge of whether this module is even
        // installed. If nothing is listening, event() is a safe no-op.
        //
        // Payload convention: a single associative array argument with
        // keys 'type' (string, free-form bucket, e.g. 'auth'/'admin'),
        // 'event' (string, free-form key), 'message' (string),
        // 'causer' (?App\Models\User, defaults to the current authenticated
        // user), 'causer_email' (?string, fallback when there's no causer
        // at all), 'properties' (?array, e.g. a before/after diff).
        Event::listen('activity.recorded', function (array $activity) {
            $causer = $activity['causer'] ?? request()->user();

            ActivityLog::create([
                'log_type' => $activity['type'],
                'event' => $activity['event'],
                'message' => $activity['message'],
                'properties' => $activity['properties'] ?? null,
                'causer_id' => $causer?->id,
                'causer_name' => $causer?->name,
                'causer_email' => $causer?->email ?? ($activity['causer_email'] ?? null),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
    }

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
