<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageSchool() === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('grades')->ignore($this->route('grade'))],
            'level' => ['required', 'integer', 'min:1', 'max:100', Rule::unique('grades')->ignore($this->route('grade'))],
        ];
    }
}
