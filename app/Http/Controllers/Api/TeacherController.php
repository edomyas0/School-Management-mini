<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TeacherRequest;
use App\Http\Resources\TeacherResource;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $teachers = Teacher::query()
            ->with(['qualifications', 'assignments.schoolClass', 'assignments.subject', 'assignments.academicYear'])
            ->when($request->string('search')->trim()->value(), function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('staff_number', 'like', "%{$search}%")
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

        return TeacherResource::collection($teachers);
    }

    public function store(TeacherRequest $request)
    {
        $teacher = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $qualification = $this->qualificationData($data);
            unset($data['qualification'], $data['institution'], $data['awarded_year']);

            $teacher = Teacher::create($data);
            $this->syncQualification($teacher, $qualification);

            return $teacher;
        });

        return (new TeacherResource($teacher->load(['qualifications', 'assignments.schoolClass', 'assignments.subject', 'assignments.academicYear'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Teacher $teacher): TeacherResource
    {
        return new TeacherResource($teacher->load(['qualifications', 'assignments.schoolClass', 'assignments.subject', 'assignments.academicYear']));
    }

    public function update(TeacherRequest $request, Teacher $teacher): TeacherResource
    {
        DB::transaction(function () use ($request, $teacher) {
            $data = $request->validated();
            $qualification = $this->qualificationData($data);
            unset($data['qualification'], $data['institution'], $data['awarded_year']);

            $teacher->update($data);
            $this->syncQualification($teacher, $qualification);
        });

        return new TeacherResource($teacher->refresh()->load(['qualifications', 'assignments.schoolClass', 'assignments.subject', 'assignments.academicYear']));
    }

    public function destroy(Teacher $teacher)
    {
        $teacher->delete();

        return response()->noContent();
    }

    private function qualificationData(array $data): ?array
    {
        if (blank($data['qualification'] ?? null)) {
            return null;
        }

        return [
            'title' => $data['qualification'],
            'institution' => $data['institution'] ?? null,
            'awarded_year' => $data['awarded_year'] ?? null,
        ];
    }

    private function syncQualification(Teacher $teacher, ?array $qualification): void
    {
        $teacher->qualifications()->update(['is_primary' => false]);

        if ($qualification === null) {
            return;
        }

        $teacher->qualifications()->updateOrCreate(
            ['title' => $qualification['title']],
            [...$qualification, 'is_primary' => true],
        );
    }
}
