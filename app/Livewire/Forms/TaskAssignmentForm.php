<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class TaskAssignmentForm extends Form
{
    #[Validate('required|string|max:200')]
    public string $title = '';

    #[Validate('nullable|string|max:2000')]
    public string $description = '';

    #[Validate('required|string|in:small,medium,large')]
    public string $complexity_tier = 'small';

    #[Validate('required|numeric|min:0.25|max:999.99')]
    public ?float $standard_hours = null;

    #[Validate('nullable|date|after_or_equal:today')]
    public string $due_date = '';

    #[Validate('required|integer|exists:users,id')]
    public ?int $assigned_to = null;
}
