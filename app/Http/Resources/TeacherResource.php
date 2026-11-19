<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'staff_number' => $this->staff_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => trim($this->first_name.' '.$this->last_name),
            'email' => $this->email,
            'phone' => $this->phone,
            'employment_type' => $this->employment_type,
            'hired_on' => $this->hired_on?->toDateString(),
            'status' => $this->status,
            'qualifications' => $this->whenLoaded('qualifications', fn () => $this->qualifications->map(fn ($qualification) => [
                'id' => $qualification->id,
                'title' => $qualification->title,
                'institution' => $qualification->institution,
                'awarded_year' => $qualification->awarded_year,
                'is_primary' => $qualification->is_primary,
            ])),
            'assignments' => $this->whenLoaded('assignments', fn () => $this->assignments->map(fn ($assignment) => [
                'class' => $assignment->schoolClass?->name,
                'subject' => $assignment->subject?->name,
                'academic_year' => $assignment->academicYear?->name,
            ])),
        ];
    }
}
