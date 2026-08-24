<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class ManualTimeLogForm extends Form
{
    #[Validate('nullable|integer|exists:accounts,id')]
    public ?int $account_id = null;

    #[Validate('nullable|integer|exists:tasks,id')]
    public ?int $task_id = null;

    #[Validate('required|date|before_or_equal:today')]
    public string $log_date = '';

    #[Validate('required|string|in:production,non_production,leave')]
    public string $hour_type = '';

    #[Validate('required|numeric|min:0.25|max:24')]
    public ?float $hours = null;

    #[Validate('nullable|string|max:255')]
    public string $notes = '';

    public function toDurationMinutes(): int
    {
        return (int) round($this->hours * 60);
    }
}
