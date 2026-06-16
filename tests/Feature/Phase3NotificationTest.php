<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Events\FeedbackCreated;
use App\Events\NotificationSent;
use App\Models\AppNotification;
use App\Models\Feedback;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class Phase3NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function seedUsers(): array
    {
        $this->seed(\Database\Seeders\LecturerUserSeeder::class);
        $this->seed(\Database\Seeders\StudentClassTestSeeder::class);

        $student = Student::first();
        $lecturer = User::where('access_code', 'LienNTD2')->first();
        $ctsv = User::factory()->create([
            'access_code' => 'CTSV01',
            'full_name' => 'CTSV Test',
            'role_label' => 'Cán bộ CTSV',
            'role' => UserRole::StudentAffairs,
            'is_active' => true,
        ]);

        return [$student, $lecturer, $ctsv];
    }

    public function test_escalation_creates_notification_for_student_affairs(): void
    {
        Event::fake([NotificationSent::class]);

        [$student, $lecturer, $ctsv] = $this->seedUsers();

        $this->actingAs($lecturer)
            ->post(route('students.feedbacks.store', $student), [
                'content' => 'Cần CTSV hỗ trợ gấp',
                'requires_escalation' => '1',
            ], ['X-SRMH-Modal' => '1'])
            ->assertOk();

        $this->assertDatabaseHas('app_notifications', [
            'user_access_code' => 'CTSV01',
            'type' => 'escalation',
        ]);

        Event::assertDispatched(NotificationSent::class);
    }

    public function test_reply_notifies_parent_author(): void
    {
        [$student, $lecturer, $ctsv] = $this->seedUsers();

        $parent = Feedback::create([
            'student_id' => $student->id,
            'author_access_code' => $lecturer->access_code,
            'author_name' => $lecturer->full_name,
            'author_role' => $lecturer->role,
            'content' => 'Cần theo dõi thêm',
        ]);

        $this->actingAs($ctsv)
            ->post(route('students.feedbacks.store', $student), [
                'content' => 'CTSV sẽ liên hệ sinh viên',
                'parent_id' => $parent->id,
            ]);

        $this->assertDatabaseHas('app_notifications', [
            'user_access_code' => $lecturer->access_code,
            'type' => 'reply',
        ]);
    }

    public function test_notifications_api_returns_unread_items(): void
    {
        [$student, $lecturer] = $this->seedUsers();

        AppNotification::create([
            'user_access_code' => $lecturer->access_code,
            'type' => 'new_feedback',
            'title' => 'Test',
            'body' => 'Nội dung test',
            'data' => ['student_id' => $student->id, 'care_status' => 'monitoring'],
        ]);

        $this->actingAs($lecturer)
            ->getJson(route('notifications.index'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonCount(1, 'items');
    }

    public function test_mark_notification_read(): void
    {
        [$student, $lecturer] = $this->seedUsers();

        $notification = AppNotification::create([
            'user_access_code' => $lecturer->access_code,
            'type' => 'new_feedback',
            'title' => 'Test',
            'body' => 'Nội dung',
            'data' => ['student_id' => $student->id],
        ]);

        $this->actingAs($lecturer)
            ->postJson(route('notifications.read', $notification))
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_feedback_created_event_is_broadcastable(): void
    {
        [$student, $lecturer] = $this->seedUsers();

        $feedback = Feedback::create([
            'student_id' => $student->id,
            'author_access_code' => $lecturer->access_code,
            'author_name' => $lecturer->full_name,
            'author_role' => $lecturer->role,
            'content' => 'Test broadcast',
        ]);

        $event = new FeedbackCreated($feedback);

        $this->assertSame('FeedbackCreated', $event->broadcastAs());
        $this->assertCount(1, $event->broadcastOn());
    }

    public function test_feedback_item_partial_requires_auth(): void
    {
        [$student, $lecturer] = $this->seedUsers();

        $feedback = Feedback::create([
            'student_id' => $student->id,
            'author_access_code' => $lecturer->access_code,
            'author_name' => $lecturer->full_name,
            'author_role' => $lecturer->role,
            'content' => 'Partial test',
        ]);

        $this->get(route('feedbacks.item', [$student, $feedback]))
            ->assertRedirect(route('login'));

        $this->actingAs($lecturer)
            ->get(route('feedbacks.item', [$student, $feedback]))
            ->assertOk()
            ->assertSee('Partial test');
    }
}
