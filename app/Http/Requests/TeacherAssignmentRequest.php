<?php

namespace App\Http\Requests;

use App\Models\SchoolClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class TeacherAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageSchool() === true;
    }

    public function rules(): array
    {
        return [
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'school_class_id' => ['required', 'integer', 'exists:school_classes,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $classYearId = SchoolClass::query()->whereKey($this->integer('school_class_id'))->value('academic_year_id');

                if ($classYearId !== $this->integer('academic_year_id')) {
                    $validator->errors()->add('academic_year_id', 'The selected class must belong to the selected academic year.');
                }
            },
        ];
    }
}
