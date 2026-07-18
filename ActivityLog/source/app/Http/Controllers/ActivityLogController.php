<?php

namespace Modules\ActivityLog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\ActivityLog\Models\ActivityLog;

class ActivityLogController extends Controller
{
    /**
     * Most recent entries loaded into the page. The table is filtered/sorted/
     * paginated entirely client-side (matching every other admin list page in
     * this app), so this caps the payload sent to the browser rather than
     * shipping the whole, unboundedly-growing table in one response.
     */
    private const MAX_ROWS = 2000;

    /**
     * Distinct badge variants a log type gets deterministically assigned
     * from, so a brand-new type string from any module gets a stable color
     * without this module needing to know it exists in advance.
     */
    private const TYPE_VARIANTS = ['info', 'primary', 'success', 'warning', 'danger'];

    /**
     * Display the activity log page.
     */
    public function index(): View
    {
        $logs = ActivityLog::query()
            ->latest('id')
            ->limit(self::MAX_ROWS)
            ->get()
            ->map(fn (ActivityLog $log): array => $this->transform($log));

        $users = User::orderBy('name')->pluck('name', 'id');

        $logTypeOptions = ActivityLog::query()
            ->distinct()
            ->orderBy('log_type')
            ->pluck('log_type')
            ->mapWithKeys(fn (string $type): array => [$type => Str::headline($type)]);

        return view('activitylog::index', [
            'logs' => $logs,
            'users' => $users,
            'logTypeOptions' => $logTypeOptions,
            'maxRows' => self::MAX_ROWS,
        ]);
    }

    /**
     * Delete activity log entries in bulk, then record the deletion itself.
     */
    public function clear(Request $request): JsonResponse
    {
        $data = $request->validate([
            'scope' => ['required', Rule::in(['all', '30_days', '7_days'])],
        ]);

        $cutoff = match ($data['scope']) {
            'all' => null,
            '30_days' => now()->subDays(30),
            '7_days' => now()->subDays(7),
        };
        $scopeLabel = match ($data['scope']) {
            'all' => 'all entries',
            '30_days' => 'older than 30 days',
            '7_days' => 'older than 7 days',
        };

        $query = $cutoff ? ActivityLog::where('created_at', '<', $cutoff) : ActivityLog::query();
        $count = $query->count();
        $query->delete();

        // Dispatched the same way every other module logs activity — this
        // module doesn't get to skip its own convention just because it
        // owns the listener.
        event('activity.recorded', [[
            'type' => 'admin',
            'event' => 'logs_cleared',
            'message' => "Cleared {$count} activity log ".($count === 1 ? 'entry' : 'entries')." ({$scopeLabel}).",
        ]]);

        return response()->json([
            'message' => "Cleared {$count} activity log ".($count === 1 ? 'entry' : 'entries').'.',
            'deleted' => $count,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(ActivityLog $log): array
    {
        return [
            'id' => $log->id,
            'created_at_ts' => $log->created_at->timestamp * 1000,
            'created_at_label' => $log->created_at->format('M j, Y, g:i A'),
            'user_id' => $log->causer_id,
            'user_name' => $log->causer_name ?? 'System',
            'user_email' => $log->causer_email,
            'log_type' => $log->log_type,
            'log_type_label' => Str::headline($log->log_type),
            'log_type_variant' => self::TYPE_VARIANTS[crc32($log->log_type) % count(self::TYPE_VARIANTS)],
            'event' => $log->event,
            'event_label' => Str::headline($log->event),
            'event_label_variant' => null,
            'message' => $log->message,
            'properties' => $log->properties,
            'ip_address' => $log->ip_address ?? '—',
            'user_agent' => $log->user_agent ?? '',
        ];
    }
}
