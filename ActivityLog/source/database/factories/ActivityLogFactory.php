<?php

namespace Modules\ActivityLog\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ActivityLog\Models\ActivityLog;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    /**
     * Illustrative example [type, event] pairs for fixture data only — not
     * a catalog the logging mechanism depends on. Any module can log any
     * type/event string; this list exists purely so fake() has something
     * representative to pick from in tests.
     *
     * @var array<int, array{0: string, 1: string}>
     */
    private const EXAMPLE_EVENTS = [
        ['auth', 'login_success'],
        ['auth', 'login_failed'],
        ['auth', 'logout'],
        ['auth', 'password_changed'],
        ['admin', 'user_created'],
        ['admin', 'user_updated'],
        ['admin', 'role_updated'],
        ['admin', 'logs_cleared'],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        [$type, $event] = fake()->randomElement(self::EXAMPLE_EVENTS);

        return [
            'log_type' => $type,
            'event' => $event,
            'message' => fake()->sentence(),
            'causer_id' => User::factory(),
            'causer_name' => fake()->name(),
            'causer_email' => fake()->safeEmail(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'created_at' => fake()->dateTimeBetween('-60 days'),
        ];
    }
}
