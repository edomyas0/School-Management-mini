<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageSchool() === true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30', Rule::unique('subjects')->ignore($this->route('subject'))],
            'name' => ['required', 'string', 'max:100', Rule::unique('subjects')->ignore($this->route('subject'))],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
