<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Modules\ActivityLog\Models\ActivityLog;
use Nwidart\Modules\Facades\Module;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    /**
     * Number of recent activity log entries shown to admins.
     */
    private const RECENT_ACTIVITY_ROWS = 8;

    /**
     * Display the dashboard — an admin overview with real stats for admins
     * (only when the Admin module is actually installed, since its pages
     * are what those stats would point you toward), generic content for
     * everyone else. One route, one view, so there's no separate "admin
     * dashboard" URL to maintain.
     */
    public function index(): View
    {
        $user = auth()->user();
        $isAdmin = Module::has('Admin') && Module::isEnabled('Admin') && $user->hasRole('admin');

        if (! $isAdmin) {
            return view('auth::dashboard', ['isAdmin' => false]);
        }

        // Modules/ActivityLog is an optional, unrelated module this one
        // never depends on to boot — guarded rather than imported normally.
        // class_exists() alone isn't enough: the class stays autoloadable
        // even when the module is disabled (composer doesn't know about
        // nwidart's enabled/disabled state), so the table itself may not
        // exist.
        $recentActivity = class_exists(ActivityLog::class) && Schema::hasTable('activity_logs')
            ? ActivityLog::query()
                ->latest('id')
                ->limit(self::RECENT_ACTIVITY_ROWS)
                ->get()
                ->map(fn (ActivityLog $log): array => [
                    'message' => $log->message,
                    'causer_name' => $log->causer_name ?? 'System',
                    'created_at_label' => $log->created_at->format('M j, Y, g:i A'),
                ])
            : collect();

        return view('auth::dashboard', [
            'isAdmin' => true,
            'totalUsers' => User::count(),
            'activeUsers' => User::where('is_active', true)->count(),
            'totalRoles' => Role::count(),
            'totalPermissions' => Permission::count(),
            'recentActivity' => $recentActivity,
        ]);
    }
}
