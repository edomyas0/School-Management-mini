<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_qualifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('institution')->nullable();
            $table->unsignedSmallInteger('awarded_year')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->unique(['teacher_id', 'title']);
        });

        Schema::create('teacher_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->unique(['teacher_id', 'school_class_id', 'subject_id']);
            $table->index(['academic_year_id', 'school_class_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_assignments');
        Schema::dropIfExists('teacher_qualifications');
    }
};
