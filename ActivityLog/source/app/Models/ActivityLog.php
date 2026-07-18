<?php

namespace Modules\ActivityLog\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ActivityLog\Database\Factories\ActivityLogFactory;

#[Fillable(['log_type', 'event', 'message', 'properties', 'causer_id', 'causer_name', 'causer_email', 'ip_address', 'user_agent'])]
class ActivityLog extends Model
{
    /** @use HasFactory<ActivityLogFactory> */
    use HasFactory;

    /**
     * Activity log rows are append-only — there's nothing to track an "updated at" for.
     */
    const UPDATED_AT = null;

    /**
     * Laravel's factory auto-resolution assumes a Database\Factories\
     * namespace, which doesn't apply to module-namespaced models.
     */
    protected static function newFactory(): ActivityLogFactory
    {
        return ActivityLogFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'properties' => 'array',
        ];
    }

    /**
     * The user who performed the logged action, if the account still exists.
     */
    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id');
    }
}
