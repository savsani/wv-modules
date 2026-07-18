<?php

namespace Modules\Auth\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Nwidart\Modules\Support\ModuleServiceProvider;

class AuthServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    protected string $name = 'Auth';

    /**
     * The lowercase version of the module name.
     */
    protected string $nameLower = 'auth';

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
        FortifyServiceProvider::class,
    ];

    /**
     * Register the service providers.
     */
    public function register(): void
    {
        parent::register();

        // spatie/laravel-permission itself stays host-installed, but this
        // module owns the tables/config as the authorization foundation
        // Admin's Roles/Permissions management UI builds on top of.
        //
        // mergeConfigFrom() only fills gaps (existing values win), which
        // would silently discard our overrides (e.g. protected_roles)
        // whenever Spatie's own provider registers its defaults first — so
        // this sets the config directly with our file winning, regardless
        // of provider registration order. See the same fix already applied
        // for Modules/Admin's laravel-impersonate config.
        $this->app['config']->set('permission', array_merge(
            $this->app['config']->get('permission', []),
            require module_path($this->name, 'config/permission.php'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Merges this module's views/components into the same unnamespaced
        // resolution path the host app already uses, so view('auth.login'),
        // <x-auth.heading>, and the host's @include('profile.partials.*')
        // calls keep working unchanged regardless of which module the file
        // physically lives in.
        View::addLocation(module_path($this->name, 'resources/views'));
        Blade::anonymousComponentPath(module_path($this->name, 'resources/views/components'));
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
