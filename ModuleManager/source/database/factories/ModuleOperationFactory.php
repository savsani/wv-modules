<?php

namespace Modules\ModuleManager\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ModuleManager\Enums\ModuleOperationAction;
use Modules\ModuleManager\Enums\ModuleOperationStatus;
use Modules\ModuleManager\Models\ModuleOperation;

/**
 * @extends Factory<ModuleOperation>
 */
class ModuleOperationFactory extends Factory
{
    protected $model = ModuleOperation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'module_key' => fake()->randomElement(['core', 'auth', 'admin', 'activitylog']),
            'action' => ModuleOperationAction::Install,
            'status' => ModuleOperationStatus::Pending,
            'from_version' => null,
            'to_version' => null,
            'output' => null,
            'error_message' => null,
            'causer_id' => User::factory(),
            'started_at' => null,
            'finished_at' => null,
        ];
    }

    public function running(): static
    {
        return $this->state([
            'status' => ModuleOperationStatus::Running,
            'started_at' => now(),
        ]);
    }

    public function succeeded(): static
    {
        return $this->state([
            'status' => ModuleOperationStatus::Succeeded,
            'from_version' => '1.0.0',
            'to_version' => '1.0.1',
            'output' => 'Updated: Core (1.0.1)',
            'started_at' => now()->subSeconds(5),
            'finished_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => ModuleOperationStatus::Failed,
            'error_message' => 'Something went wrong.',
            'started_at' => now()->subSeconds(5),
            'finished_at' => now(),
        ]);
    }
}
