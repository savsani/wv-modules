<?php

namespace Modules\ModuleManager\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\ModuleManager\Database\Factories\ModuleOperationFactory;
use Modules\ModuleManager\Enums\ModuleOperationAction;
use Modules\ModuleManager\Enums\ModuleOperationStatus;

#[Fillable(['module_key', 'action', 'status', 'from_version', 'to_version', 'output', 'error_message', 'causer_id', 'started_at', 'finished_at'])]
class ModuleOperation extends Model
{
    /** @use HasFactory<ModuleOperationFactory> */
    use HasFactory;

    /**
     * Laravel's factory auto-resolution assumes a Database\Factories\
     * namespace, which doesn't apply to module-namespaced models.
     */
    protected static function newFactory(): ModuleOperationFactory
    {
        return ModuleOperationFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => ModuleOperationAction::class,
            'status' => ModuleOperationStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    /**
     * The admin who triggered this operation, if the account still exists.
     */
    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id');
    }
}
