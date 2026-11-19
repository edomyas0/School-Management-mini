<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $student = $this->route('student');

        return $this->user()?->canManageSchool() === true
            && ($student === null || $this->user()->can('update', $student));
    }

    public function rules(): array
    {
        $student = $this->route('student');

        return [
            'admission_number' => ['required', 'string', 'max:50', Rule::unique('students')->ignore($student)],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['required', Rule::in(['female', 'male'])],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('students')->ignore($student)],
            'phone' => ['nullable', 'string', 'max:32'],
            'status' => ['required', Rule::in(['active', 'inactive', 'graduated', 'transferred'])],
            'class_id' => ['nullable', 'integer', 'exists:school_classes,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('class_id') === '') {
            $this->merge(['class_id' => null]);
        }
    }
}
