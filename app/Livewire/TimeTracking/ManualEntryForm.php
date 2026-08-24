<?php

namespace App\Livewire\TimeTracking;

use App\Domain\TimeTracking\Services\ManualTimeEntryService;
use App\Livewire\Forms\ManualTimeLogForm;
use App\Models\TimeLog;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ManualEntryForm extends Component
{
    public ManualTimeLogForm $form;

    public function mount(): void
    {
        $this->authorize('create', TimeLog::class);
        $this->form->log_date = now()->toDateString();
    }

    #[Computed]
    public function accounts()
    {
        return auth()->user()->accounts()->orderBy('name')->get();
    }

    #[Computed]
    public function tasks()
    {
        if (! $this->form->account_id) {
            return collect();
        }

        return auth()->user()->assignedTasks()
            ->where('account_id', $this->form->account_id)
            ->orderBy('title')
            ->get();
    }

    public function save(ManualTimeEntryService $service): void
    {
        $this->authorize('create', TimeLog::class);

        $this->form->validate();

        $service->log(auth()->user(), [
            'account_id' => $this->form->account_id,
            'task_id' => $this->form->task_id,
            'log_date' => $this->form->log_date,
            'hour_type' => $this->form->hour_type,
            'duration_minutes' => $this->form->toDurationMinutes(),
            'notes' => $this->form->notes !== '' ? $this->form->notes : null,
        ]);

        $this->form->reset(['account_id', 'task_id', 'hours', 'notes']);
        $this->form->log_date = now()->toDateString();
        $this->form->hour_type = 'production';

        $this->dispatch('time-log-created');
    }

    public function render()
    {
        return view('livewire.time-tracking.manual-entry-form');
    }
}
