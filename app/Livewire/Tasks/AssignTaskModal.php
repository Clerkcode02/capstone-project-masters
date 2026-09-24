<?php

namespace App\Livewire\Tasks;

use App\Domain\Tasks\Enums\ComplexityTier;
use App\Domain\Tasks\Exceptions\OverAllocationWarning;
use App\Domain\Tasks\Services\TaskAssignmentService;
use App\Livewire\Forms\TaskAssignmentForm;
use App\Models\Account;
use App\Models\Task;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Creates and assigns a task for one account, running the pre-assignment
 * over-allocation check before the task is written (capstone Ch. 3, M3).
 * Never silently blocks and never silently allows: an over-threshold
 * assignment always shows the real numbers, and the manager decides.
 */
class AssignTaskModal extends Component
{
    public Account $account;

    public TaskAssignmentForm $form;

    public bool $confirmingOverAllocation = false;

    public ?int $evaluationCurrentScore = null;

    public ?int $evaluationProspectiveScore = null;

    public ?int $evaluationThreshold = null;

    /** @var array<int, array{id: int, name: string, score: int}> */
    public array $alternatives = [];

    public function mount(Account $account): void
    {
        $this->authorize('create', Task::class);

        $this->account = $account;
    }

    #[Computed]
    public function employees()
    {
        return $this->account->users()->where('is_active', true)->orderBy('first_name')->get();
    }

    public function save(TaskAssignmentService $service): void
    {
        $this->authorize('create', Task::class);

        $this->form->validate();

        try {
            $service->assign($this->taskData(), auth()->user(), confirmedOverride: $this->confirmingOverAllocation);
        } catch (OverAllocationWarning $warning) {
            $this->showWarning($warning);

            return;
        }

        $this->resetForm();
        $this->dispatch('task-assigned');
    }

    public function cancelOverAllocation(): void
    {
        $this->confirmingOverAllocation = false;
        $this->alternatives = [];
    }

    /**
     * Changing the candidate or tier invalidates a shown warning — re-run
     * the check fresh rather than let a stale confirmation slip through.
     */
    public function updated(string $name): void
    {
        if (in_array($name, ['form.assigned_to', 'form.complexity_tier'], true)) {
            $this->cancelOverAllocation();
        }
    }

    /**
     * @return array{title: string, description: ?string, account_id: int, assigned_to: int, complexity_tier: string, standard_hours: float, due_date: ?string}
     */
    private function taskData(): array
    {
        return [
            'title' => $this->form->title,
            'description' => $this->form->description !== '' ? $this->form->description : null,
            'account_id' => $this->account->id,
            'assigned_to' => $this->form->assigned_to,
            'complexity_tier' => $this->form->complexity_tier,
            'standard_hours' => $this->form->standard_hours,
            'due_date' => $this->form->due_date !== '' ? $this->form->due_date : null,
        ];
    }

    private function showWarning(OverAllocationWarning $warning): void
    {
        $evaluation = $warning->evaluation;

        $this->confirmingOverAllocation = true;
        $this->evaluationCurrentScore = $evaluation->currentScore;
        $this->evaluationProspectiveScore = $evaluation->prospectiveScore;
        $this->evaluationThreshold = $evaluation->threshold;

        $this->alternatives = $evaluation->alternatives
            ->map(fn (array $candidate) => [
                'id' => $candidate['user']->id,
                'name' => $candidate['user']->full_name,
                'score' => $candidate['score'],
            ])
            ->all();
    }

    private function resetForm(): void
    {
        $this->form->reset();
        $this->form->complexity_tier = ComplexityTier::Small->value;
        $this->confirmingOverAllocation = false;
        $this->alternatives = [];
    }

    public function render()
    {
        return view('livewire.tasks.assign-task-modal');
    }
}
