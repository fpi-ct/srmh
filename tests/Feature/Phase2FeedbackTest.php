<?php

namespace Tests\Feature;

use App\Models\Feedback;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase2FeedbackTest extends TestCase
{
    use RefreshDatabase;

    private function seedStudentForLecturer(): array
    {
        $this->seed(\Database\Seeders\LecturerUserSeeder::class);
        $this->seed(\Database\Seeders\StudentClassTestSeeder::class);

        $student = Student::first();
        $lecturer = User::where('access_code', 'LienNTD2')->first();

        return [$student, $lecturer];
    }

    public function test_lecturer_can_post_feedback(): void
    {
        [$student, $lecturer] = $this->seedStudentForLecturer();

        $this->actingAs($lecturer)
            ->post(route('students.feedbacks.store', $student), [
                'content' => 'Sinh viên vắng nhiều buổi gần đây',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('feedbacks', [
            'student_id' => $student->id,
            'author_access_code' => 'LienNTD2',
            'content' => 'Sinh viên vắng nhiều buổi gần đây',
        ]);
    }

    public function test_lecturer_can_reply_feedback(): void
    {
        [$student, $lecturer] = $this->seedStudentForLecturer();

        $parent = Feedback::create([
            'student_id' => $student->id,
            'author_access_code' => 'QuangLN14',
            'author_name' => 'Quang',
            'author_role' => 'lecturer',
            'content' => 'Cần theo dõi thêm',
        ]);

        $this->actingAs($lecturer)
            ->post(route('students.feedbacks.store', $student), [
                'content' => 'Đã nhắc nhở qua Zalo',
                'parent_id' => $parent->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('feedbacks', [
            'parent_id' => $parent->id,
            'content' => 'Đã nhắc nhở qua Zalo',
        ]);
    }

    public function test_lecturer_can_update_care_status(): void
    {
        [$student, $lecturer] = $this->seedStudentForLecturer();

        $this->actingAs($lecturer)
            ->patch(route('students.care-status', $student), [
                'care_status' => 'critical',
                'reason' => 'Vắng quá 20%',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'care_status' => 'critical',
        ]);
    }

    public function test_student_affairs_can_resolve_escalation(): void
    {
        [$student, $lecturer] = $this->seedStudentForLecturer();
        $ctsv = User::where('access_code', 'HaiLP3')->first();

        $feedback = Feedback::create([
            'student_id' => $student->id,
            'author_access_code' => $lecturer->access_code,
            'author_name' => $lecturer->full_name,
            'author_role' => 'lecturer',
            'content' => 'Cần can thiệp',
            'requires_escalation' => true,
        ]);

        $this->actingAs($ctsv)
            ->post(route('feedbacks.resolve', [$student, $feedback]), [
                'note' => 'Đã gọi phụ huynh',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotNull($feedback->fresh()->escalation_resolved_at);
        $this->assertDatabaseHas('feedbacks', [
            'student_id' => $student->id,
            'author_access_code' => 'HaiLP3',
            'content' => 'Đã gọi phụ huynh',
        ]);
    }
}
