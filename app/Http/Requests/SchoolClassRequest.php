<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SchoolClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageSchool() === true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'grade_id' => ['required', 'integer', 'exists:grades,id'],
            'class_teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
            'name' => ['required', 'string', 'max:100'],
            'section' => [
                'required',
                'string',
                'max:50',
                Rule::unique('school_classes')->where(fn ($query) => $query
                    ->where('academic_year_id', $this->input('academic_year_id'))
                    ->where('grade_id', $this->input('grade_id')))
                    ->ignore($this->route('class')),
            ],
            'capacity' => ['required', 'integer', 'min:1', 'max:500'],
        ];
    }
}
