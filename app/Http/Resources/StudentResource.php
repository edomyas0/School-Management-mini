<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $enrollment = $this->whenLoaded('enrollments', fn () => $this->enrollments
            ->where('status', 'active')
            ->sortByDesc(fn ($item) => $item->academicYear?->starts_on)
            ->first());

        return [
            'id' => $this->id,
            'admission_number' => $this->admission_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => trim($this->first_name.' '.$this->last_name),
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'gender' => $this->gender,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'class' => $enrollment?->schoolClass ? [
                'id' => $enrollment->schoolClass->id,
                'name' => $enrollment->schoolClass->name,
                'section' => $enrollment->schoolClass->section,
                'grade' => $enrollment->schoolClass->grade?->name,
                'academic_year' => $enrollment->academicYear?->name,
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
