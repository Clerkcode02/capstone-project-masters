<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignAccountUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manageAssignments', $this->route('account'));
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
