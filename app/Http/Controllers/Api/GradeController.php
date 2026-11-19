<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GradeRequest;
use App\Http\Resources\GradeResource;
use App\Models\Grade;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function index(Request $request)
    {
        return GradeResource::collection(Grade::query()->orderBy('level')->paginate(max(1, min($request->integer('per_page', 100), 100))));
    }

    public function store(GradeRequest $request)
    {
        return (new GradeResource(Grade::create($request->validated())))->response()->setStatusCode(201);
    }

    public function update(GradeRequest $request, Grade $grade): GradeResource
    {
        $grade->update($request->validated());

        return new GradeResource($grade);
    }

    public function destroy(Grade $grade)
    {
        if ($grade->classes()->exists()) {
            return response()->json(['message' => 'This grade is assigned to classes and cannot be deleted.'], 409);
        }

        $grade->delete();

        return response()->noContent();
    }
}
