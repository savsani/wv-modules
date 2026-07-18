<?php

namespace Modules\Admin\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Lab404\Impersonate\Services\ImpersonateManager;
use Modules\Admin\Support\ActivityLogger;

class ImpersonateController extends Controller
{
    public function __construct(private readonly ImpersonateManager $manager) {}

    /**
     * Start impersonating the given user.
     */
    public function take(Request $request, User $user): RedirectResponse
    {
        /** @var User $admin */
        $admin = $request->user();

        abort_if($user->is($admin), 403, "You can't impersonate yourself.");
        abort_if($this->manager->isImpersonating(), 403, 'You are already impersonating a user.');
        abort_unless($admin->canImpersonate(), 403);
        abort_unless($user->canBeImpersonated(), 403, "This user can't be impersonated.");

        $this->manager->take($admin, $user);

        ActivityLogger::record('admin', 'impersonation_started', "Started impersonating \"{$user->email}\".", $admin);

        return redirect()->route('dashboard');
    }

    /**
     * Stop impersonating and return to the original account.
     */
    public function stop(): RedirectResponse
    {
        abort_unless($this->manager->isImpersonating(), 403);

        /** @var User $target */
        $target = auth()->user();
        $admin = User::find(session(config('laravel-impersonate.session_key')));

        $this->manager->leave();

        ActivityLogger::record('admin', 'impersonation_stopped', "Stopped impersonating \"{$target->email}\".", $admin);

        return redirect()->route('admin.users.index');
    }
}
