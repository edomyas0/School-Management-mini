<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('admission_number')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 24)->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('phone', 32)->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('school_class_id')->constrained('school_classes')->restrictOnDelete();
            $table->date('enrolled_on');
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->unique(['student_id', 'academic_year_id']);
            $table->index(['school_class_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('students');
    }
};
