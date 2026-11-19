<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        $teacher = $this->route('teacher');

        return $this->user()?->canManageSchool() === true
            && ($teacher === null || $this->user()->can('update', $teacher));
    }

    public function rules(): array
    {
        $teacher = $this->route('teacher');

        return [
            'staff_number' => ['required', 'string', 'max:50', Rule::unique('teachers')->ignore($teacher)],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('teachers')->ignore($teacher)],
            'phone' => ['nullable', 'string', 'max:32'],
            'employment_type' => ['required', Rule::in(['full_time', 'part_time', 'contract'])],
            'hired_on' => ['required', 'date', 'before_or_equal:today'],
            'status' => ['required', Rule::in(['active', 'on_leave', 'inactive'])],
            'qualification' => ['nullable', 'string', 'max:150'],
            'institution' => ['nullable', 'string', 'max:150'],
            'awarded_year' => ['nullable', 'integer', 'between:1950,'.now()->year],
        ];
    }
}
