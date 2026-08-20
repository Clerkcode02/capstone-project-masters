<?php

namespace App\Http\Requests\TimeTracking;

use App\Domain\TimeTracking\Enums\HourType;
use App\Models\TimeLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTimeLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var TimeLog $timeLog */
        $timeLog = $this->route('time_log');

        return $this->user()?->can('update', $timeLog) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'log_date' => ['required', 'date', 'before_or_equal:today'],
            'task_id' => ['nullable', 'integer', Rule::exists('tasks', 'id')],
            'account_id' => ['nullable', 'integer', Rule::exists('accounts', 'id')],
            'hour_type' => ['required', Rule::enum(HourType::class)],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
