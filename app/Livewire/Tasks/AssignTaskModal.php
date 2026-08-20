<?php

namespace App\Livewire\Tasks;

use App\Domain\Tasks\DTOs\OverAllocationCheckResult;
use App\Domain\Tasks\Services\TaskAssignmentService;
use App\Models\Task;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Component;

class AssignTaskModal extends Component
{
    public Task $task;

    public ?int $selectedCandidateId = null;

    public bool $showWarning = false;

    public function mount(Task $task): void
    {
        $this->authorize('assign', $task);

        $this->task = $task;
    }

    #[Computed]
    public function candidates()
    {
        return $this->task->account->users()
            ->where('users.is_active', true)
            ->get();
    }

    #[Computed]
    public function check(): ?OverAllocationCheckResult
    {
        if (! $this->selectedCandidateId) {
            return null;
        }

        return app(TaskAssignmentService::class)->evaluate(
            $this->task,
            User::findOrFail($this->selectedCandidateId),
        );
    }

    public function selectCandidate(int $userId): void
    {
        $this->authorize('assign', $this->task);

        $this->selectedCandidateId = $userId;
        unset($this->check);
        $this->showWarning = (bool) $this->check?->exceedsThreshold;
    }

    public function confirmAssign(bool $override = false): void
    {
        $this->authorize('assign', $this->task);

        if (! $this->selectedCandidateId) {
            return;
        }

        $check = $this->check;

        if ($check?->exceedsThreshold && ! $override) {
            $this->showWarning = true;

            return;
        }

        app(TaskAssignmentService::class)->assign(
            task: $this->task,
            candidate: User::findOrFail($this->selectedCandidateId),
            actor: auth()->user(),
            override: (bool) $check?->exceedsThreshold && $override,
        );

        $this->dispatch('task-assigned', taskId: $this->task->id);
        $this->dispatch('close-modal', 'assign-task-'.$this->task->id);

        $this->reset('selectedCandidateId', 'showWarning');
    }

    public function cancel(): void
    {
        $this->reset('selectedCandidateId', 'showWarning');
        $this->dispatch('close-modal', 'assign-task-'.$this->task->id);
    }

    public function render()
    {
        return view('livewire.tasks.assign-task-modal');
    }
}
