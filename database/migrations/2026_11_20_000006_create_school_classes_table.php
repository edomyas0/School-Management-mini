<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('grade_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->string('name');
            $table->string('section')->default('A');
            $table->unsignedSmallInteger('capacity')->default(30);
            $table->timestamps();
            $table->unique(['academic_year_id', 'grade_id', 'section']);
            $table->index(['academic_year_id', 'grade_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_classes');
    }
};
