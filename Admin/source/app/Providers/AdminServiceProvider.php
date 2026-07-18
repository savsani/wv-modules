<?php

namespace Modules\Admin\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Nwidart\Modules\Support\ModuleServiceProvider;

class AdminServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Admin';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'admin';

    /**
     * Command classes to register.
     *
     * @var string[]
     */
    // protected array $commands = [];

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    /**
     * Register the service providers.
     */
    public function register(): void
    {
        parent::register();

        // lab404/laravel-impersonate itself stays host-installed (like
        // spatie/laravel-permission), but only this module's
        // ImpersonateController actually uses the feature, so its config
        // lives here rather than at the host config/ level.
        //
        // mergeConfigFrom() only fills gaps (existing values win), which
        // silently discards our overrides whenever the package's own
        // provider happens to register its defaults first — so this sets
        // the config directly with our file winning, regardless of
        // provider registration order.
        $this->app['config']->set('laravel-impersonate', array_merge(
            $this->app['config']->get('laravel-impersonate', []),
            require module_path($this->name, 'config/laravel-impersonate.php'),
        ));
    }

    /**
     * Define module schedules.
     *
     * @param  $schedule
     */
    // protected function configureSchedules(Schedule $schedule): void
    // {
    //     $schedule->command('inspire')->hourly();
    // }
}
