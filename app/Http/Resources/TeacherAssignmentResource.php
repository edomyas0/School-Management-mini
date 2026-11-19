<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'teacher' => $this->teacher ? [
                'id' => $this->teacher->id,
                'staff_number' => $this->teacher->staff_number,
                'first_name' => $this->teacher->first_name,
                'last_name' => $this->teacher->last_name,
            ] : null,
            'school_class' => $this->schoolClass ? [
                'id' => $this->schoolClass->id,
                'name' => $this->schoolClass->name,
                'section' => $this->schoolClass->section,
            ] : null,
            'subject' => $this->subject ? [
                'id' => $this->subject->id,
                'code' => $this->subject->code,
                'name' => $this->subject->name,
            ] : null,
            'academic_year' => $this->academicYear ? [
                'id' => $this->academicYear->id,
                'name' => $this->academicYear->name,
            ] : null,
        ];
    }
}
