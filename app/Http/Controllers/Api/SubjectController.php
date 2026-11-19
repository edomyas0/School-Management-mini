<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubjectRequest;
use App\Http\Resources\SubjectResource;
use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index(Request $request)
    {
        return SubjectResource::collection(
            Subject::query()->orderBy('name')->paginate(max(1, min($request->integer('per_page', 100), 100))),
        );
    }

    public function store(SubjectRequest $request)
    {
        return (new SubjectResource(Subject::create($request->validated())))->response()->setStatusCode(201);
    }

    public function update(SubjectRequest $request, Subject $subject): SubjectResource
    {
        $subject->update($request->validated());

        return new SubjectResource($subject);
    }

    public function destroy(Subject $subject)
    {
        if ($subject->teacherAssignments()->exists()) {
            return response()->json(['message' => 'This subject is assigned to teachers and cannot be deleted.'], 409);
        }

        $subject->delete();

        return response()->noContent();
    }
}
