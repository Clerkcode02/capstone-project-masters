<?php

namespace App\Domain\Tasks\Services;

use App\Domain\Administration\Services\SettingsService;
use App\Domain\Tasks\Enums\ComplexityTier;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Domain\Tasks\Exceptions\InvalidTaskStatusTransitionException;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TaskService
{
    public function __construct(private readonly SettingsService $settings) {}

    /**
     * @param  array{title:string,description:?string,account_id:int,standard_hours:float,complexity_tier:string,due_date:?string}  $data
     */
    public function create(array $data, User $creator): Task
    {
        return DB::transaction(function () use ($data, $creator) {
            $tier = ComplexityTier::from($data['complexity_tier']);

            return Task::query()->create([
                'reference' => $this->nextReference((int) now()->year),
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'account_id' => $data['account_id'],
                'created_by' => $creator->id,
                'complexity_tier' => $tier,
                'complexity_weight' => $this->settings->complexityWeight($tier),
                'standard_hours' => $data['standard_hours'],
                'status' => TaskStatus::Pending,
                'due_date' => $data['due_date'] ?? null,
            ]);
        });
    }

    /**
     * @param  array{title:string,description:?string,account_id:int,standard_hours:float,complexity_tier:string,due_date:?string}  $data
     */
    public function update(Task $task, array $data): Task
    {
        $tier = ComplexityTier::from($data['complexity_tier']);

        $task->fill([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'account_id' => $data['account_id'],
            'complexity_tier' => $tier,
            'complexity_weight' => $this->settings->complexityWeight($tier),
            'standard_hours' => $data['standard_hours'],
            'due_date' => $data['due_date'] ?? null,
        ]);

        $task->save();

        return $task;
    }

    public function delete(Task $task): void
    {
        $task->delete();
    }

    public function start(Task $task): Task
    {
        return $this->transition($task, TaskStatus::Pending, TaskStatus::InProgress, [
            'started_at' => now(),
        ]);
    }

    public function complete(Task $task): Task
    {
        return $this->transition($task, TaskStatus::InProgress, TaskStatus::Completed, [
            'completed_at' => now(),
        ]);
    }

    public function nextReference(int $year): string
    {
        $prefix = "TSK-{$year}-";

        $last = Task::withTrashed()
            ->where('reference', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('reference')
            ->value('reference');

        $sequence = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    private function transition(Task $task, TaskStatus $from, TaskStatus $to, array $attributes): Task
    {
        if ($task->status !== $from) {
            throw new InvalidTaskStatusTransitionException(
                "Cannot move task {$task->reference} from {$task->status->value} to {$to->value}."
            );
        }

        $task->fill(array_merge($attributes, ['status' => $to]));
        $task->save();

        return $task;
    }
}
