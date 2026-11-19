<?php

use App\Http\Controllers\Api\AcademicYearController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\GradeController;
use App\Http\Controllers\Api\SchoolClassController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\TeacherAssignmentController;
use App\Http\Controllers\Api\TeacherController;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/dashboard', DashboardController::class);

    Route::get('/students', [StudentController::class, 'index'])->middleware('can:viewAny,'.Student::class);
    Route::post('/students', [StudentController::class, 'store'])->middleware('can:create,'.Student::class);
    Route::get('/students/{student}', [StudentController::class, 'show'])->middleware('can:view,student');
    Route::put('/students/{student}', [StudentController::class, 'update'])->middleware('can:update,student');
    Route::delete('/students/{student}', [StudentController::class, 'destroy'])->middleware('can:delete,student');

    Route::get('/teachers', [TeacherController::class, 'index'])->middleware('can:viewAny,'.Teacher::class);
    Route::post('/teachers', [TeacherController::class, 'store'])->middleware('can:create,'.Teacher::class);
    Route::get('/teachers/{teacher}', [TeacherController::class, 'show'])->middleware('can:view,teacher');
    Route::put('/teachers/{teacher}', [TeacherController::class, 'update'])->middleware('can:update,teacher');
    Route::delete('/teachers/{teacher}', [TeacherController::class, 'destroy'])->middleware('can:delete,teacher');

    Route::middleware('can:manage-school')->group(function () {
        Route::get('/academic-years', [AcademicYearController::class, 'index']);
        Route::post('/academic-years', [AcademicYearController::class, 'store']);
        Route::put('/academic-years/{academicYear}', [AcademicYearController::class, 'update']);
        Route::delete('/academic-years/{academicYear}', [AcademicYearController::class, 'destroy']);

        Route::get('/grades', [GradeController::class, 'index']);
        Route::post('/grades', [GradeController::class, 'store']);
        Route::put('/grades/{grade}', [GradeController::class, 'update']);
        Route::delete('/grades/{grade}', [GradeController::class, 'destroy']);

        Route::get('/classes', [SchoolClassController::class, 'index']);
        Route::post('/classes', [SchoolClassController::class, 'store']);
        Route::put('/classes/{class}', [SchoolClassController::class, 'update']);
        Route::delete('/classes/{class}', [SchoolClassController::class, 'destroy']);

        Route::get('/subjects', [SubjectController::class, 'index']);
        Route::post('/subjects', [SubjectController::class, 'store']);
        Route::put('/subjects/{subject}', [SubjectController::class, 'update']);
        Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy']);

        Route::get('/teacher-assignments', [TeacherAssignmentController::class, 'index']);
        Route::post('/teacher-assignments', [TeacherAssignmentController::class, 'store']);
        Route::delete('/teacher-assignments/{assignment}', [TeacherAssignmentController::class, 'destroy']);
    });
});
