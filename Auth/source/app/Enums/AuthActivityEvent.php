<?php

namespace Modules\Auth\Enums;

/**
 * Event keys for the auth activity this module logs (registration, login,
 * password changes, two-factor, ...) — used only for type-safe call sites
 * when dispatching the 'activity.recorded' event (see
 * Modules\Auth\Support\ActivityLogger). Whatever's listening for that event
 * (e.g. Modules/ActivityLog) renders these labels generically via
 * Str::headline() on the raw string — nothing needs to know this enum
 * exists, so there's no catalog to keep in sync.
 */
enum AuthActivityEvent: string
{
    case Registered = 'registered';
    case LoginSuccess = 'login_success';
    case LoginFailed = 'login_failed';
    case LoginLockedOut = 'login_locked_out';
    case LoginBlockedInactive = 'login_blocked_inactive';
    case SessionTerminatedInactive = 'session_terminated_inactive';
    case Logout = 'logout';
    case PasswordResetLinkRequested = 'password_reset_link_requested';
    case PasswordReset = 'password_reset';
    case PasswordChanged = 'password_changed';
    case ProfileUpdated = 'profile_updated';
    case EmailVerified = 'email_verified';
    case TwoFactorEnabled = 'two_factor_enabled';
    case TwoFactorDisabled = 'two_factor_disabled';
    case TwoFactorChallengeFailed = 'two_factor_challenge_failed';
    case TwoFactorRecoveryCodesRegenerated = 'two_factor_recovery_codes_regenerated';
    case TwoFactorRecoveryCodeUsed = 'two_factor_recovery_code_used';
}
