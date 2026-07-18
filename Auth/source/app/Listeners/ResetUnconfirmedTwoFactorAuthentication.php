<?php

namespace Modules\Auth\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Logout;

class ResetUnconfirmedTwoFactorAuthentication
{
    /**
     * Discard an in-progress, unconfirmed two-factor authentication setup on logout
     * so the next session starts over from the "Enable" button instead of resuming
     * the previous QR code.
     */
    public function handle(Logout $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        if ($user->two_factor_secret !== null && $user->two_factor_confirmed_at === null) {
            $user->forceFill([
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
            ])->save();
        }
    }
}
