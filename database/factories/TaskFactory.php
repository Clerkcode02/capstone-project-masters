<?php

namespace Database\Factories;

use App\Domain\Tasks\Enums\ComplexityTier;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Models\Account;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'reference' => 'TSK-'.now()->year.'-'.fake()->unique()->numerify('####'),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'account_id' => Account::factory(),
            'assigned_to' => User::factory(),
            'created_by' => User::factory(),
            'complexity_tier' => ComplexityTier::Medium->value,
            'complexity_weight' => 3,
            'standard_hours' => 9.00,
            'actual_hours' => 0,
            'status' => TaskStatus::Pending->value,
            'is_bottleneck' => false,
            'due_date' => null,
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn () => [
            'status' => TaskStatus::InProgress->value,
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => TaskStatus::Completed->value,
            'started_at' => now()->subDays(3),
            'completed_at' => now(),
        ]);
    }
}
