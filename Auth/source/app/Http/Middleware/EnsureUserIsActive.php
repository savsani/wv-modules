<?php

namespace Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Enums\AuthActivityEvent;
use Modules\Auth\Support\ActivityLogger;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Force-logout a user the moment their account is deactivated, even if
     * they were already authenticated when it happened, instead of waiting
     * for their session to expire naturally.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->is_active) {
            return $next($request);
        }

        ActivityLogger::record(
            'auth',
            AuthActivityEvent::SessionTerminatedInactive->value,
            "User's session was terminated because the account was deactivated.",
            $user,
        );

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('Your account is not active. Please contact an administrator.'),
            ], 401);
        }

        return redirect()->route('login')->with('status', 'account-deactivated');
    }
}
