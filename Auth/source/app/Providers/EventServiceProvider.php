<?php

namespace Modules\Auth\Providers;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Laravel\Fortify\Events\PasswordUpdatedViaController;
use Laravel\Fortify\Events\RecoveryCodeReplaced;
use Laravel\Fortify\Events\RecoveryCodesGenerated;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;
use Modules\Auth\Listeners\LogAuthActivity;
use Modules\Auth\Listeners\ResetUnconfirmedTwoFactorAuthentication;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * LogAuthActivity has one method per event (not a single handle()), so
     * these are registered explicitly in boot() rather than via this array,
     * which only supports single-handle() listener classes.
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

        Event::listen(Logout::class, ResetUnconfirmedTwoFactorAuthentication::class);

        Event::listen(Registered::class, [LogAuthActivity::class, 'onRegistered']);
        Event::listen(Login::class, [LogAuthActivity::class, 'onLogin']);
        Event::listen(Failed::class, [LogAuthActivity::class, 'onFailed']);
        Event::listen(Logout::class, [LogAuthActivity::class, 'onLogout']);
        Event::listen(PasswordResetLinkSent::class, [LogAuthActivity::class, 'onPasswordResetLinkSent']);
        Event::listen(PasswordReset::class, [LogAuthActivity::class, 'onPasswordReset']);
        Event::listen(PasswordUpdatedViaController::class, [LogAuthActivity::class, 'onPasswordUpdated']);
        Event::listen(Verified::class, [LogAuthActivity::class, 'onVerified']);
        Event::listen(TwoFactorAuthenticationConfirmed::class, [LogAuthActivity::class, 'onTwoFactorConfirmed']);
        Event::listen(TwoFactorAuthenticationDisabled::class, [LogAuthActivity::class, 'onTwoFactorDisabled']);
        Event::listen(TwoFactorAuthenticationFailed::class, [LogAuthActivity::class, 'onTwoFactorChallengeFailed']);
        Event::listen(RecoveryCodesGenerated::class, [LogAuthActivity::class, 'onRecoveryCodesGenerated']);
        Event::listen(RecoveryCodeReplaced::class, [LogAuthActivity::class, 'onRecoveryCodeReplaced']);
    }

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
