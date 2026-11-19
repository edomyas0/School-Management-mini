<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SchoolClassResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'academic_year_id' => $this->academic_year_id,
            'academic_year' => $this->whenLoaded('academicYear', fn () => $this->academicYear?->name),
            'grade_id' => $this->grade_id,
            'grade' => $this->whenLoaded('grade', fn () => $this->grade?->name),
            'class_teacher_id' => $this->class_teacher_id,
            'class_teacher' => $this->whenLoaded('classTeacher', fn () => $this->classTeacher
                ? $this->classTeacher->first_name.' '.$this->classTeacher->last_name
                : null),
            'name' => $this->name,
            'section' => $this->section,
            'capacity' => $this->capacity,
            'students_count' => $this->whenCounted('enrollments'),
        ];
    }
}
