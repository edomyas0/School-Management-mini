<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudentRequest;
use App\Http\Resources\StudentResource;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $students = Student::query()
            ->with(['enrollments.academicYear', 'enrollments.schoolClass.grade'])
            ->when($request->string('search')->trim()->value(), function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('admission_number', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->input('status'), fn ($query, $status) => $query->where('status', $status))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(max(1, min($request->integer('per_page', 15), 100)))
            ->appends($request->query());

        return StudentResource::collection($students);
    }

    public function store(StudentRequest $request)
    {
        $student = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $classId = $data['class_id'] ?? null;
            $hasClassField = array_key_exists('class_id', $data);
            unset($data['class_id']);

            $student = Student::create($data);
            if ($hasClassField) {
                $this->syncEnrollment($student, $classId);
            }

            return $student;
        });

        return (new StudentResource($student->load(['enrollments.academicYear', 'enrollments.schoolClass.grade'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Student $student): StudentResource
    {
        return new StudentResource($student->load(['enrollments.academicYear', 'enrollments.schoolClass.grade']));
    }

    public function update(StudentRequest $request, Student $student): StudentResource
    {
        DB::transaction(function () use ($request, $student) {
            $data = $request->validated();
            $classId = $data['class_id'] ?? null;
            $hasClassField = array_key_exists('class_id', $data);
            unset($data['class_id']);

            $student->update($data);
            if ($hasClassField) {
                $this->syncEnrollment($student, $classId);
            }
        });

        return new StudentResource($student->refresh()->load(['enrollments.academicYear', 'enrollments.schoolClass.grade']));
    }

    public function destroy(Student $student)
    {
        $student->delete();

        return response()->noContent();
    }

    private function syncEnrollment(Student $student, ?int $classId): void
    {
        if ($classId === null) {
            $enrollment = $student->enrollments()->where('status', 'active')->latest('enrolled_on')->first();
            $enrollment?->update(['status' => 'transferred']);

            return;
        }

        $class = SchoolClass::query()->lockForUpdate()->findOrFail($classId);
        $activeEnrollments = Enrollment::query()
            ->where('school_class_id', $class->id)
            ->where('status', 'active')
            ->whereHas('student');

        if ($student->exists) {
            $activeEnrollments->where('student_id', '!=', $student->id);
        }

        if ($activeEnrollments->count() >= $class->capacity) {
            throw ValidationException::withMessages([
                'class_id' => ['This class has reached its enrollment capacity.'],
            ]);
        }

        $student->enrollments()->updateOrCreate(
            ['academic_year_id' => $class->academic_year_id],
            [
                'school_class_id' => $class->id,
                'enrolled_on' => today(),
                'status' => 'active',
            ],
        );
    }
}
