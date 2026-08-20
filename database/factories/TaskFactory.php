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

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => 'TSK-'.now()->year.'-'.fake()->unique()->numerify('####'),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'account_id' => Account::factory(),
            'assigned_to' => null,
            'created_by' => User::factory(),
            'complexity_tier' => ComplexityTier::Medium,
            'complexity_weight' => 3,
            'standard_hours' => fake()->randomFloat(2, 1, 20),
            'status' => TaskStatus::Pending,
            'due_date' => fake()->dateTimeBetween('now', '+1 month'),
        ];
    }

    public function assignedTo(User $user): static
    {
        return $this->state(fn () => ['assigned_to' => $user->id]);
    }

    public function inProgress(): static
    {
        return $this->state(fn () => [
            'status' => TaskStatus::InProgress,
            'started_at' => now(),
        ]);
    }
}
