<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\TeacherQualification;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Demo accounts and records must never be seeded into production.');
        }

        User::firstOrCreate(
            ['email' => 'school-admin@example.test'],
            ['name' => 'School Administrator', 'password' => 'SchoolAdmin#2026', 'role' => 'administrator'],
        );
        User::firstOrCreate(
            ['email' => 'registrar@example.test'],
            ['name' => 'Maya Okafor', 'password' => 'Registrar#2026', 'role' => 'registrar'],
        );

        $currentYear = AcademicYear::firstOrCreate(
            ['name' => '2026–2027'],
            ['starts_on' => '2026-09-01', 'ends_on' => '2027-06-30', 'is_current' => true],
        );
        AcademicYear::firstOrCreate(
            ['name' => '2027–2028'],
            ['starts_on' => '2027-09-01', 'ends_on' => '2028-06-30', 'is_current' => false],
        );

        $grades = collect([
            ['name' => 'Grade 6', 'level' => 6],
            ['name' => 'Grade 7', 'level' => 7],
            ['name' => 'Grade 8', 'level' => 8],
        ])->mapWithKeys(fn (array $grade) => [$grade['level'] => Grade::firstOrCreate(['level' => $grade['level']], $grade)]);

        $subjects = collect([
            ['code' => 'MAT-06', 'name' => 'Mathematics'],
            ['code' => 'ENG-06', 'name' => 'English Language'],
            ['code' => 'SCI-06', 'name' => 'General Science'],
            ['code' => 'HIS-06', 'name' => 'World History'],
            ['code' => 'GEO-06', 'name' => 'Geography'],
            ['code' => 'ART-06', 'name' => 'Visual Arts'],
        ])->mapWithKeys(fn (array $subject) => [
            $subject['code'] => Subject::firstOrCreate(['code' => $subject['code']], ['name' => $subject['name']]),
        ]);

        $teachers = collect([
            ['staff_number' => 'T-2026-001', 'first_name' => 'Amara', 'last_name' => 'Mensah', 'email' => 'amara.mensah@example.test', 'phone' => '+1 555 010 2401', 'employment_type' => 'full_time', 'hired_on' => '2021-08-15', 'qualification' => 'B.Ed. Mathematics', 'institution' => 'Northbridge University', 'awarded_year' => 2018],
            ['staff_number' => 'T-2026-002', 'first_name' => 'Daniel', 'last_name' => 'Okafor', 'email' => 'daniel.okafor@example.test', 'phone' => '+1 555 010 2402', 'employment_type' => 'full_time', 'hired_on' => '2020-08-15', 'qualification' => 'M.A. English', 'institution' => 'Lakeside College', 'awarded_year' => 2017],
            ['staff_number' => 'T-2026-003', 'first_name' => 'Lina', 'last_name' => 'Chen', 'email' => 'lina.chen@example.test', 'phone' => '+1 555 010 2403', 'employment_type' => 'full_time', 'hired_on' => '2022-01-10', 'qualification' => 'B.Sc. Biology', 'institution' => 'Central State University', 'awarded_year' => 2019],
            ['staff_number' => 'T-2026-004', 'first_name' => 'Samuel', 'last_name' => 'Brooks', 'email' => 'samuel.brooks@example.test', 'phone' => '+1 555 010 2404', 'employment_type' => 'part_time', 'hired_on' => '2023-08-20', 'qualification' => 'M.A. History', 'institution' => 'Eastfield University', 'awarded_year' => 2020],
        ])->mapWithKeys(function (array $details) {
            [$staffNumber, $qualification, $institution, $awardedYear] = [
                $details['staff_number'],
                $details['qualification'],
                $details['institution'],
                $details['awarded_year'],
            ];
            unset($details['qualification'], $details['institution'], $details['awarded_year']);

            $teacher = Teacher::firstOrCreate(['staff_number' => $staffNumber], $details);
            TeacherQualification::firstOrCreate(
                ['teacher_id' => $teacher->id, 'title' => $qualification],
                ['institution' => $institution, 'awarded_year' => $awardedYear, 'is_primary' => true],
            );

            return [$staffNumber => $teacher];
        });

        $classes = collect([
            ['name' => 'Grade 6', 'grade' => 6, 'section' => 'A', 'teacher' => 'T-2026-001'],
            ['name' => 'Grade 6', 'grade' => 6, 'section' => 'B', 'teacher' => 'T-2026-002'],
            ['name' => 'Grade 7', 'grade' => 7, 'section' => 'A', 'teacher' => 'T-2026-002'],
            ['name' => 'Grade 7', 'grade' => 7, 'section' => 'B', 'teacher' => 'T-2026-003'],
            ['name' => 'Grade 8', 'grade' => 8, 'section' => 'A', 'teacher' => 'T-2026-003'],
            ['name' => 'Grade 8', 'grade' => 8, 'section' => 'B', 'teacher' => 'T-2026-004'],
        ])->mapWithKeys(function (array $details) use ($currentYear, $grades, $teachers) {
            $class = SchoolClass::firstOrCreate(
                [
                    'academic_year_id' => $currentYear->id,
                    'grade_id' => $grades[$details['grade']]->id,
                    'section' => $details['section'],
                ],
                [
                    'name' => $details['name'],
                    'class_teacher_id' => $teachers[$details['teacher']]->id,
                    'capacity' => 28,
                ],
            );

            return [$details['grade'].$details['section'] => $class];
        });

        foreach ($teachers->values() as $index => $teacher) {
            $class = $classes[$index < 2 ? '6A' : '7A'];
            $subject = $subjects->values()[$index];
            TeacherAssignment::firstOrCreate([
                'teacher_id' => $teacher->id,
                'school_class_id' => $class->id,
                'subject_id' => $subject->id,
                'academic_year_id' => $currentYear->id,
            ]);
        }

        $students = [
            ['Olivia', 'Reed', 'female', '6A'],
            ['Ethan', 'Patel', 'male', '6A'],
            ['Sofia', 'Diaz', 'female', '6A'],
            ['Noah', 'Wilson', 'male', '6A'],
            ['Ava', 'Thompson', 'female', '6B'],
            ['Liam', 'Jackson', 'male', '6B'],
            ['Isabella', 'Brown', 'female', '7A'],
            ['Mason', 'Garcia', 'male', '7A'],
            ['Mia', 'Robinson', 'female', '7A'],
            ['Lucas', 'Clark', 'male', '7B'],
            ['Amelia', 'Lewis', 'female', '8A'],
            ['James', 'Walker', 'male', '8B'],
        ];

        foreach ($students as $index => [$firstName, $lastName, $gender, $classKey]) {
            $student = Student::firstOrCreate(
                ['admission_number' => sprintf('ADM-2026-%03d', $index + 1)],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'date_of_birth' => sprintf('%04d-0%d-%02d', 2010 + ($index % 3), 3 + ($index % 8), 4 + ($index % 20)),
                    'gender' => $gender,
                    'email' => sprintf('student.%03d@example.test', $index + 1),
                    'phone' => sprintf('+1 555 011 %04d', $index + 1),
                    'status' => 'active',
                ],
            );

            Enrollment::firstOrCreate(
                ['student_id' => $student->id, 'academic_year_id' => $currentYear->id],
                ['school_class_id' => $classes[$classKey]->id, 'enrolled_on' => '2026-09-01', 'status' => 'active'],
            );
        }
    }
}
