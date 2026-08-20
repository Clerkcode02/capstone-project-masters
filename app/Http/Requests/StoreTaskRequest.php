<?php

namespace App\Http\Requests;

use App\Domain\Tasks\Enums\ComplexityTier;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Task::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'standard_hours' => ['required', 'numeric', 'min:0.25', 'max:999.99'],
            'complexity_tier' => ['required', Rule::enum(ComplexityTier::class)],
            'due_date' => ['nullable', 'date'],
        ];
    }
}
