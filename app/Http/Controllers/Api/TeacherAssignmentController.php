<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TeacherAssignmentRequest;
use App\Http\Resources\TeacherAssignmentResource;
use App\Models\TeacherAssignment;
use Illuminate\Http\Request;

class TeacherAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $assignments = TeacherAssignment::query()
            ->with(['teacher', 'schoolClass', 'subject', 'academicYear'])
            ->orderByDesc('academic_year_id')
            ->paginate(max(1, min($request->integer('per_page', 100), 100)));

        return TeacherAssignmentResource::collection($assignments);
    }

    public function store(TeacherAssignmentRequest $request)
    {
        $assignment = TeacherAssignment::query()->firstOrCreate($request->validated());
        $assignment->load(['teacher', 'schoolClass', 'subject', 'academicYear']);

        return (new TeacherAssignmentResource($assignment))
            ->response()
            ->setStatusCode($assignment->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(TeacherAssignment $assignment)
    {
        $assignment->delete();

        return response()->noContent();
    }
}
