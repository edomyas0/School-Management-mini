<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolManagementApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_school_account_can_sign_in_and_read_its_role(): void
    {
        User::create([
            'name' => 'School Administrator',
            'email' => 'admin@example.test',
            'password' => 'secret-password',
            'role' => 'administrator',
        ]);

        $this->withHeader('Origin', 'http://localhost')
            ->postJson('/api/login', ['email' => 'admin@example.test', 'password' => 'secret-password'])
            ->assertOk()
            ->assertJsonPath('data.role', 'administrator');
    }

    public function test_a_student_can_be_registered_assigned_and_archived(): void
    {
        $admin = $this->administrator();
        $registrar = $this->registrar();
        $year = AcademicYear::create([
            'name' => '2026-2027',
            'starts_on' => '2026-09-01',
            'ends_on' => '2027-06-30',
            'is_current' => true,
        ]);
        $grade = Grade::create(['name' => 'Grade 7', 'level' => 7]);
        $class = SchoolClass::create([
            'academic_year_id' => $year->id,
            'grade_id' => $grade->id,
            'name' => 'Grade 7',
            'section' => 'A',
            'capacity' => 30,
        ]);

        $response = $this->actingAs($registrar)->postJson('/api/students', [
            'admission_number' => 'ADM-2026-001',
            'first_name' => 'Olivia',
            'last_name' => 'Reed',
            'date_of_birth' => '2012-04-17',
            'gender' => 'female',
            'email' => 'olivia@example.test',
            'phone' => '+1 555 010 0100',
            'status' => 'active',
            'class_id' => (string) $class->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.admission_number', 'ADM-2026-001')
            ->assertJsonPath('data.class.grade', 'Grade 7')
            ->assertJsonPath('data.class.section', 'A');

        $studentId = $response->json('data.id');

        $this->actingAs($admin)->putJson("/api/students/{$studentId}", [
            'admission_number' => 'ADM-2026-001',
            'first_name' => 'Olivia',
            'last_name' => 'Reed',
            'date_of_birth' => '2012-04-17',
            'gender' => 'female',
            'email' => 'olivia@example.test',
            'phone' => '+1 555 010 0100',
            'status' => 'active',
            'class_id' => null,
        ])->assertOk()->assertJsonPath('data.class', null);

        $this->assertDatabaseHas('enrollments', ['student_id' => $studentId, 'status' => 'transferred']);

        $this->actingAs($admin)->deleteJson("/api/students/{$studentId}")->assertNoContent();
        $this->assertSoftDeleted('students', ['id' => $studentId]);
    }

    public function test_a_student_can_have_a_date_of_birth_of_today_in_the_school_timezone(): void
    {
        $registrar = $this->registrar();
        $today = now(config('app.timezone'))->toDateString();

        $this->actingAs($registrar)->postJson('/api/students', [
            'admission_number' => 'ADM-TODAY-001',
            'first_name' => 'Today',
            'last_name' => 'Student',
            'date_of_birth' => $today,
            'gender' => 'female',
            'status' => 'active',
        ])->assertCreated()
            ->assertJsonPath('data.date_of_birth', $today);

        $tomorrow = now(config('app.timezone'))->addDay()->toDateString();

        $this->actingAs($registrar)->postJson('/api/students', [
            'admission_number' => 'ADM-TOMORROW-001',
            'first_name' => 'Future',
            'last_name' => 'Student',
            'date_of_birth' => $tomorrow,
            'gender' => 'male',
            'status' => 'active',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('date_of_birth');
    }

    public function test_student_gender_only_accepts_male_or_female(): void
    {
        $registrar = $this->registrar();

        foreach (['other', 'prefer_not_to_say'] as $gender) {
            $this->actingAs($registrar)->postJson('/api/students', [
                'admission_number' => 'ADM-GENDER-'.strtoupper(str_replace('_', '-', $gender)),
                'first_name' => 'Test',
                'last_name' => 'Student',
                'gender' => $gender,
                'status' => 'active',
            ])->assertUnprocessable()
                ->assertJsonValidationErrors('gender');
        }

        $this->actingAs($registrar)->postJson('/api/students', [
            'admission_number' => 'ADM-GENDER-MISSING',
            'first_name' => 'Test',
            'last_name' => 'Student',
            'status' => 'active',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('gender');

        $this->assertDatabaseCount('students', 0);
    }

    public function test_teacher_profiles_persist_qualifications_and_employment_details(): void
    {
        $admin = $this->administrator();
        $registrar = $this->registrar();

        $response = $this->actingAs($registrar)->postJson('/api/teachers', [
            'staff_number' => 'T-001',
            'first_name' => 'Lina',
            'last_name' => 'Chen',
            'email' => 'lina@example.test',
            'phone' => '+1 555 010 0200',
            'employment_type' => 'full_time',
            'hired_on' => '2022-08-15',
            'status' => 'active',
            'qualification' => 'B.Sc. Biology',
            'institution' => 'Central State University',
            'awarded_year' => 2019,
        ]);

        $response->assertCreated()->assertJsonPath('data.qualifications.0.title', 'B.Sc. Biology');

        $teacherId = $response->json('data.id');
        $this->assertDatabaseHas('teacher_qualifications', [
            'teacher_id' => $teacherId,
            'title' => 'B.Sc. Biology',
            'institution' => 'Central State University',
            'is_primary' => true,
        ]);

        $this->actingAs($admin)->putJson("/api/teachers/{$teacherId}", [
            'staff_number' => 'T-001',
            'first_name' => 'Lina',
            'last_name' => 'Chen',
            'email' => 'lina@example.test',
            'phone' => null,
            'employment_type' => 'part_time',
            'hired_on' => '2022-08-15',
            'status' => 'active',
            'qualification' => '',
            'institution' => '',
            'awarded_year' => null,
        ])->assertOk();

        $this->assertDatabaseHas('teacher_qualifications', ['teacher_id' => $teacherId, 'is_primary' => false]);
    }

    public function test_teacher_assignments_cannot_cross_academic_years(): void
    {
        $admin = $this->administrator();
        $teacher = Teacher::create([
            'staff_number' => 'T-002',
            'first_name' => 'Maya',
            'last_name' => 'Okafor',
            'email' => 'maya@example.test',
            'employment_type' => 'full_time',
            'hired_on' => '2022-08-15',
            'status' => 'active',
        ]);
        $yearOne = AcademicYear::create([
            'name' => '2025-2026',
            'starts_on' => '2025-09-01',
            'ends_on' => '2026-06-30',
            'is_current' => false,
        ]);
        $yearTwo = AcademicYear::create([
            'name' => '2026-2027',
            'starts_on' => '2026-09-01',
            'ends_on' => '2027-06-30',
            'is_current' => true,
        ]);
        $grade = Grade::create(['name' => 'Grade 8', 'level' => 8]);
        $class = SchoolClass::create([
            'academic_year_id' => $yearOne->id,
            'grade_id' => $grade->id,
            'name' => 'Grade 8',
            'section' => 'A',
            'capacity' => 30,
        ]);
        $subject = Subject::create(['code' => 'SCI-08', 'name' => 'Science']);

        $this->actingAs($admin)->postJson('/api/teacher-assignments', [
            'teacher_id' => $teacher->id,
            'school_class_id' => $class->id,
            'subject_id' => $subject->id,
            'academic_year_id' => $yearTwo->id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('academic_year_id');
    }

    public function test_teacher_assignments_are_idempotent_and_return_shaped_records(): void
    {
        $admin = $this->administrator();
        $teacher = Teacher::create([
            'staff_number' => 'T-003',
            'first_name' => 'Amara',
            'last_name' => 'Mensah',
            'email' => 'amara@example.test',
            'employment_type' => 'full_time',
            'hired_on' => '2021-08-15',
            'status' => 'active',
        ]);
        $year = AcademicYear::create([
            'name' => '2026-2027',
            'starts_on' => '2026-09-01',
            'ends_on' => '2027-06-30',
            'is_current' => true,
        ]);
        $grade = Grade::create(['name' => 'Grade 6', 'level' => 6]);
        $class = SchoolClass::create([
            'academic_year_id' => $year->id,
            'grade_id' => $grade->id,
            'name' => 'Grade 6',
            'section' => 'A',
            'capacity' => 30,
        ]);
        $subject = Subject::create(['code' => 'MAT-06', 'name' => 'Mathematics']);
        $assignment = [
            'teacher_id' => $teacher->id,
            'school_class_id' => $class->id,
            'subject_id' => $subject->id,
            'academic_year_id' => $year->id,
        ];

        $this->actingAs($admin)->postJson('/api/teacher-assignments', $assignment)
            ->assertCreated()
            ->assertJsonPath('data.teacher.staff_number', 'T-003')
            ->assertJsonPath('data.school_class.section', 'A')
            ->assertJsonPath('data.subject.code', 'MAT-06');

        $this->actingAs($admin)->postJson('/api/teacher-assignments', $assignment)->assertOk();
        $this->assertDatabaseCount('teacher_assignments', 1);
        $this->actingAs($admin)->getJson('/api/teacher-assignments')
            ->assertOk()
            ->assertJsonPath('data.0.academic_year.name', '2026-2027');
    }

    public function test_academic_setup_creates_classes_and_enforces_section_uniqueness(): void
    {
        $admin = $this->administrator();
        $year = AcademicYear::create([
            'name' => '2026-2027',
            'starts_on' => '2026-09-01',
            'ends_on' => '2027-06-30',
            'is_current' => true,
        ]);
        $grade = Grade::create(['name' => 'Grade 7', 'level' => 7]);
        $class = [
            'academic_year_id' => $year->id,
            'grade_id' => $grade->id,
            'name' => 'Grade 7',
            'section' => 'A',
            'capacity' => 30,
        ];

        $this->actingAs($admin)->postJson('/api/classes', $class)
            ->assertCreated()
            ->assertJsonPath('data.grade', 'Grade 7')
            ->assertJsonPath('data.academic_year', '2026-2027');

        $this->actingAs($admin)->postJson('/api/classes', [...$class, 'name' => 'Another class'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('section');

        $this->assertDatabaseCount('school_classes', 1);
    }

    public function test_class_capacity_is_enforced_when_assigning_a_student(): void
    {
        $admin = $this->administrator();
        $year = AcademicYear::create([
            'name' => '2026-2027',
            'starts_on' => '2026-09-01',
            'ends_on' => '2027-06-30',
            'is_current' => true,
        ]);
        $grade = Grade::create(['name' => 'Grade 6', 'level' => 6]);
        $class = SchoolClass::create([
            'academic_year_id' => $year->id,
            'grade_id' => $grade->id,
            'name' => 'Grade 6',
            'section' => 'A',
            'capacity' => 1,
        ]);
        $student = Student::create([
            'admission_number' => 'ADM-2026-010',
            'first_name' => 'Mia',
            'last_name' => 'Robinson',
            'status' => 'active',
        ]);
        $student->enrollments()->create([
            'academic_year_id' => $year->id,
            'school_class_id' => $class->id,
            'enrolled_on' => '2026-09-01',
            'status' => 'active',
        ]);

        $this->actingAs($this->registrar())->postJson('/api/students', [
            'admission_number' => 'ADM-2026-011',
            'first_name' => 'Noah',
            'last_name' => 'Wilson',
            'gender' => 'male',
            'status' => 'active',
            'class_id' => $class->id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('class_id');

        $this->assertDatabaseMissing('students', ['admission_number' => 'ADM-2026-011']);
    }

    public function test_non_management_users_cannot_read_student_records(): void
    {
        $teacher = User::create([
            'name' => 'Class Teacher',
            'email' => 'teacher@example.test',
            'password' => 'secret-password',
            'role' => 'teacher',
        ]);

        $this->actingAs($teacher)->getJson('/api/students')->assertForbidden();
    }

    public function test_administrators_cannot_register_students_or_teachers(): void
    {
        $admin = $this->administrator();

        $this->actingAs($admin)->postJson('/api/students', [])->assertForbidden();
        $this->actingAs($admin)->postJson('/api/teachers', [])->assertForbidden();

        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('teachers', 0);
    }

    public function test_dashboard_counts_come_from_persisted_records(): void
    {
        $admin = $this->administrator();
        Student::create([
            'admission_number' => 'ADM-2026-002',
            'first_name' => 'Ethan',
            'last_name' => 'Patel',
            'status' => 'active',
        ]);

        $this->actingAs($admin)->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('statistics.students', 1)
            ->assertJsonPath('statistics.teachers', 0)
            ->assertJsonPath('attendance.available', false);
    }

    private function administrator(): User
    {
        return User::create([
            'name' => 'School Administrator',
            'email' => 'admin@example.test',
            'password' => 'secret-password',
            'role' => 'administrator',
        ]);
    }

    private function registrar(): User
    {
        return User::create([
            'name' => 'School Registrar',
            'email' => 'registrar@example.test',
            'password' => 'secret-password',
            'role' => 'registrar',
        ]);
    }
}
