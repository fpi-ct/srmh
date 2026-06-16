<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\StudentClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase1ApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\LecturerUserSeeder::class);
    }

    public function test_login_with_valid_access_code(): void
    {
        $response = $this->postJson('/api/auth/login', ['access_code' => 'LienNTD2']);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['access_code', 'role']]);
    }

    public function test_inactive_user_is_rejected(): void
    {
        User::where('access_code', 'LienNTD2')->update(['is_active' => false]);

        $this->postJson('/api/auth/login', ['access_code' => 'LienNTD2'])
            ->assertForbidden();
    }

    public function test_lecturer_sees_students_in_shared_class(): void
    {
        $this->seed(\Database\Seeders\StudentClassTestSeeder::class);

        $token = $this->postJson('/api/auth/login', ['access_code' => 'LienNTD2'])
            ->json('token');

        $this->withToken($token)
            ->getJson('/api/students')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_department_head_sees_faculty_students(): void
    {
        $student = Student::create([
            'student_code' => 'BC99999',
            'full_name' => 'Test Computing Student',
            'care_status' => 'monitoring',
        ]);

        $lecturer = User::where('access_code', 'QuangLN14')->first();

        StudentClass::create([
            'student_id' => $student->id,
            'lecturer_id' => $lecturer->id,
            'class_name' => 'SE09201',
            'subject_code' => 'PRJ301',
            'group_id' => 1,
            'faculty' => 'Computing',
            'semester' => 'Summer 2026',
        ]);

        $token = $this->postJson('/api/auth/login', ['access_code' => 'NhuomTV'])
            ->json('token');

        $this->withToken($token)
            ->getJson('/api/students/stats')
            ->assertOk()
            ->assertJson(['total' => 1, 'monitoring' => 1]);
    }
}
