<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicYearRequest;
use App\Http\Resources\AcademicYearResource;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AcademicYearController extends Controller
{
    public function index(Request $request)
    {
        return AcademicYearResource::collection(
            AcademicYear::query()->orderByDesc('starts_on')->paginate(max(1, min($request->integer('per_page', 50), 100))),
        );
    }

    public function store(AcademicYearRequest $request)
    {
        $year = DB::transaction(function () use ($request) {
            $data = $request->validated();

            if ($data['is_current']) {
                AcademicYear::query()->where('is_current', true)->update(['is_current' => false]);
            }

            return AcademicYear::create($data);
        });

        return (new AcademicYearResource($year))->response()->setStatusCode(201);
    }

    public function update(AcademicYearRequest $request, AcademicYear $academicYear): AcademicYearResource
    {
        DB::transaction(function () use ($request, $academicYear) {
            $data = $request->validated();

            if ($data['is_current']) {
                AcademicYear::query()->where('is_current', true)->whereKeyNot($academicYear->id)->update(['is_current' => false]);
            }

            $academicYear->update($data);
        });

        return new AcademicYearResource($academicYear->refresh());
    }

    public function destroy(AcademicYear $academicYear)
    {
        if ($academicYear->classes()->exists() || $academicYear->enrollments()->exists()) {
            return response()->json(['message' => 'This academic year is in use and cannot be deleted.'], 409);
        }

        $academicYear->delete();

        return response()->noContent();
    }
}
