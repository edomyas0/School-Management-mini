<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageSchool() === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('academic_years')->ignore($this->route('academic_year'))],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
            'is_current' => ['required', 'boolean'],
        ];
    }
}
