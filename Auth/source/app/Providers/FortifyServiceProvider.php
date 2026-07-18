<?php

namespace Modules\Auth\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorDisabledResponse as TwoFactorDisabledResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Laravel\Fortify\Fortify;
use Modules\Auth\Actions\Fortify\CreateNewUser;
use Modules\Auth\Actions\Fortify\ResetUserPassword;
use Modules\Auth\Actions\Fortify\UpdateUserPassword;
use Modules\Auth\Actions\Fortify\UpdateUserProfileInformation;
use Modules\Auth\Enums\AuthActivityEvent;
use Modules\Auth\Support\ActivityLogger;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TwoFactorDisabledResponseContract::class, function () {
            return new class implements TwoFactorDisabledResponseContract
            {
                public function toResponse($request)
                {
                    if ($request->wantsJson()) {
                        return new JsonResponse('', 200);
                    }

                    $status = $request->boolean('cancelled')
                        ? 'two-factor-authentication-setup-cancelled'
                        : Fortify::TWO_FACTOR_AUTHENTICATION_DISABLED;

                    return back()->with('status', $status);
                }
            };
        });

        // Both replicate Fortify's own stock response classes exactly,
        // only swapping the redirect fallback for route('dashboard')
        // (Fortify's default is a static config('fortify.home') path).
        // redirect()->intended() is preserved so a deep-linked pre-login
        // URL still takes priority. The dashboard itself decides what to
        // show based on role — see Modules\Auth\Http\Controllers\DashboardController.
        $this->app->singleton(LoginResponseContract::class, function () {
            return new class implements LoginResponseContract
            {
                public function toResponse($request)
                {
                    return $request->wantsJson()
                        ? new JsonResponse(['two_factor' => false])
                        : redirect()->intended(route('dashboard'));
                }
            };
        });

        $this->app->singleton(TwoFactorLoginResponseContract::class, function () {
            return new class implements TwoFactorLoginResponseContract
            {
                public function toResponse($request)
                {
                    return $request->wantsJson()
                        ? new JsonResponse('', 204)
                        : redirect()->intended(route('dashboard'));
                }
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(fn () => Password::min(8)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->uncompromised());

        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where(Fortify::username(), $request->{Fortify::username()})->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                return null;
            }

            if (! $user->is_active) {
                ActivityLogger::record(
                    'auth',
                    AuthActivityEvent::LoginBlockedInactive->value,
                    'Login rejected because the account is deactivated.',
                    $user,
                );

                throw ValidationException::withMessages([
                    Fortify::username() => __('Your account is not active. Please contact an administrator.'),
                ]);
            }

            return $user;
        });

        Fortify::loginView(fn () => view('auth.login'));
        Fortify::registerView(fn () => view('auth.register'));
        Fortify::requestPasswordResetLinkView(fn () => view('auth.forgot-password'));
        Fortify::resetPasswordView(fn ($request) => view('auth.reset-password', ['request' => $request]));
        Fortify::twoFactorChallengeView(fn () => view('auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('auth.confirm-password'));
        Fortify::verifyEmailView(fn () => view('auth.verify-email'));

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            // This app resolves 'login' to a named limiter (config('fortify.limiters.login')),
            // so Fortify's pipeline uses this route-level `throttle:login` middleware instead of
            // its EnsureLoginIsNotThrottled action — meaning Illuminate\Auth\Events\Lockout never
            // fires here. ->response() is the only hook the throttle middleware exposes for a
            // locked-out attempt, so logging happens here rather than via an event listener.
            return Limit::perMinute(5)->by($throttleKey)->response(function (Request $request, array $headers) {
                ActivityLogger::record(
                    'auth',
                    AuthActivityEvent::LoginLockedOut->value,
                    'Login temporarily locked after too many failed attempts.',
                    causerEmail: $request->input(Fortify::username()),
                );

                return response('Too Many Attempts.', 429, $headers);
            });
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
