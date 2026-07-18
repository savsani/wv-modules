<?php

namespace Modules\Auth\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Laravel\Fortify\Events\PasswordUpdatedViaController;
use Laravel\Fortify\Events\RecoveryCodeReplaced;
use Laravel\Fortify\Events\RecoveryCodesGenerated;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;
use Laravel\Fortify\Fortify;
use Modules\Auth\Enums\AuthActivityEvent;
use Modules\Auth\Support\ActivityLogger;

/**
 * Records an activity log entry for each framework/Fortify auth event this
 * app cares about. One listener class, one method per event, each registered
 * individually in EventServiceProvider::boot() — keeps every hook point in
 * one place instead of scattering closures across the service provider.
 */
class LogAuthActivity
{
    public function onRegistered(Registered $event): void
    {
        ActivityLogger::record('auth', AuthActivityEvent::Registered->value, 'New user account registered.', $event->user);
    }

    public function onLogin(Login $event): void
    {
        ActivityLogger::record('auth', AuthActivityEvent::LoginSuccess->value, 'User logged in successfully.', $event->user);
    }

    public function onFailed(Failed $event): void
    {
        $email = $event->credentials[Fortify::username()] ?? null;

        // Fortify's own authenticateUsing() pipeline always dispatches this
        // event with a null user (unlike the framework guard's default
        // attempt(), which resolves the user by credentials even when the
        // password is wrong) — resolve it here so the audit log still
        // attributes the attempt to a known account when the email matches.
        $user = $event->user ?? (is_string($email) ? User::where(Fortify::username(), $email)->first() : null);

        ActivityLogger::record(
            'auth',
            AuthActivityEvent::LoginFailed->value,
            'Invalid credentials provided.',
            $user,
            is_string($email) ? $email : null,
        );
    }

    public function onLogout(Logout $event): void
    {
        if (! $event->user) {
            return;
        }

        ActivityLogger::record('auth', AuthActivityEvent::Logout->value, 'User logged out.', $event->user);
    }

    public function onPasswordResetLinkSent(PasswordResetLinkSent $event): void
    {
        ActivityLogger::record('auth', AuthActivityEvent::PasswordResetLinkRequested->value, 'User requested a password reset link.', $event->user);
    }

    public function onPasswordReset(PasswordReset $event): void
    {
        ActivityLogger::record('auth', AuthActivityEvent::PasswordReset->value, 'User successfully reset their password via email link.', $event->user);
    }

    public function onPasswordUpdated(PasswordUpdatedViaController $event): void
    {
        ActivityLogger::record('auth', AuthActivityEvent::PasswordChanged->value, 'User successfully changed their account password.', $event->user);
    }

    public function onVerified(Verified $event): void
    {
        ActivityLogger::record('auth', AuthActivityEvent::EmailVerified->value, 'User verified their email address.', $event->user);
    }

    public function onTwoFactorConfirmed(TwoFactorAuthenticationConfirmed $event): void
    {
        ActivityLogger::record('auth', AuthActivityEvent::TwoFactorEnabled->value, 'User enabled two-factor authentication.', $event->user);
    }

    public function onTwoFactorDisabled(TwoFactorAuthenticationDisabled $event): void
    {
        ActivityLogger::record('auth', AuthActivityEvent::TwoFactorDisabled->value, 'User disabled two-factor authentication.', $event->user);
    }

    public function onTwoFactorChallengeFailed(TwoFactorAuthenticationFailed $event): void
    {
        ActivityLogger::record('auth', AuthActivityEvent::TwoFactorChallengeFailed->value, 'Invalid two-factor authentication code provided during login.', $event->user);
    }

    public function onRecoveryCodesGenerated(RecoveryCodesGenerated $event): void
    {
        ActivityLogger::record('auth', AuthActivityEvent::TwoFactorRecoveryCodesRegenerated->value, 'User regenerated their two-factor recovery codes.', $event->user);
    }

    /**
     * Fires when a recovery code is consumed to complete the two-factor login
     * challenge (as opposed to regenerating the whole set, above) — the used
     * code itself is never logged, only that one was used.
     */
    public function onRecoveryCodeReplaced(RecoveryCodeReplaced $event): void
    {
        ActivityLogger::record('auth', AuthActivityEvent::TwoFactorRecoveryCodeUsed->value, 'User signed in using a two-factor recovery code.', $event->user);
    }
}
