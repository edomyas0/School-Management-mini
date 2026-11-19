<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $studentActivity = Student::query()
            ->latest()
            ->limit(5)
            ->get(['id', 'first_name', 'last_name', 'created_at'])
            ->map(fn (Student $student) => [
                'id' => 'student-'.$student->id,
                'type' => 'student_registered',
                'description' => $student->first_name.' '.$student->last_name.' was registered',
                'occurred_at' => $student->created_at->toIso8601String(),
            ]);

        $teacherActivity = Teacher::query()
            ->latest()
            ->limit(5)
            ->get(['id', 'first_name', 'last_name', 'created_at'])
            ->map(fn (Teacher $teacher) => [
                'id' => 'teacher-'.$teacher->id,
                'type' => 'teacher_added',
                'description' => $teacher->first_name.' '.$teacher->last_name.' was added to staff',
                'occurred_at' => $teacher->created_at->toIso8601String(),
            ]);

        $employees = Teacher::count() + User::query()
            ->whereIn('role', ['administrator', 'registrar', 'accountant'])
            ->whereDoesntHave('teacher')
            ->count();

        $upcomingYears = AcademicYear::query()
            ->where('starts_on', '>=', today())
            ->orderBy('starts_on')
            ->limit(3)
            ->get(['id', 'name', 'starts_on', 'ends_on']);

        return response()->json([
            'statistics' => [
                'students' => Student::count(),
                'teachers' => Teacher::count(),
                'employees' => $employees,
                'classes' => SchoolClass::count(),
            ],
            'academic_year' => AcademicYear::query()->where('is_current', true)->first(['id', 'name', 'starts_on', 'ends_on']),
            'attendance' => ['available' => false],
            'recent_activities' => $studentActivity
                ->concat($teacherActivity)
                ->sortByDesc('occurred_at')
                ->take(6)
                ->values(),
            'upcoming_events' => $upcomingYears,
        ]);
    }
}
