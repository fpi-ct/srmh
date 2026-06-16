<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\User;
use Illuminate\Database\Seeder;

class StudentClassTestSeeder extends Seeder
{
    public function run(): void
    {
        $student = Student::firstOrCreate(
            ['student_code' => 'BC00596'],
            ['full_name' => 'Test Student', 'care_status' => 'stable']
        );

        foreach (['LienNTD2', 'TrinhLNT'] as $accessCode) {
            $lecturer = User::firstOrCreate(
                ['access_code' => $accessCode],
                [
                    'full_name' => $accessCode,
                    'role_label' => 'Giảng viên',
                    'role' => UserRole::Lecturer,
                    'is_active' => true,
                ]
            );

            StudentClass::firstOrCreate([
                'student_id' => $student->id,
                'lecturer_id' => $lecturer->id,
                'class_name' => 'ENT4011.01',
                'subject_code' => 'ENT4011',
                'group_id' => 1199,
            ], [
                'faculty' => 'English',
                'semester' => 'Summer 2026',
            ]);
        }
    }
}
