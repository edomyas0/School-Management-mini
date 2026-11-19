<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SchoolClassRequest;
use App\Http\Resources\SchoolClassResource;
use App\Models\SchoolClass;
use Illuminate\Http\Request;

class SchoolClassController extends Controller
{
    public function index(Request $request)
    {
        $classes = SchoolClass::query()
            ->with(['academicYear', 'grade', 'classTeacher'])
            ->withCount('enrollments')
            ->orderBy('name')
            ->orderBy('section')
            ->paginate(max(1, min($request->integer('per_page', 100), 100)));

        return SchoolClassResource::collection($classes);
    }

    public function store(SchoolClassRequest $request)
    {
        $class = SchoolClass::create($request->validated());

        return (new SchoolClassResource($class->load(['academicYear', 'grade', 'classTeacher'])->loadCount('enrollments')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(SchoolClassRequest $request, SchoolClass $class): SchoolClassResource
    {
        $class->update($request->validated());

        return new SchoolClassResource($class->refresh()->load(['academicYear', 'grade', 'classTeacher'])->loadCount('enrollments'));
    }

    public function destroy(SchoolClass $class)
    {
        if ($class->enrollments()->exists() || $class->teacherAssignments()->exists()) {
            return response()->json(['message' => 'This class has student or teacher records and cannot be deleted.'], 409);
        }

        $class->delete();

        return response()->noContent();
    }
}
