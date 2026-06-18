<?php

namespace Tests\Feature;

use App\Enums\CareStatus;
use App\Enums\UserRole;
use App\Models\BugReport;
use App\Models\Feedback;
use App\Models\Student;
use App\Models\StudentClass;
use App\Models\User;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class Phase4Test extends TestCase
{
    use RefreshDatabase;

    private function seedContext(): array
    {
        $this->seed(\Database\Seeders\LecturerUserSeeder::class);
        $this->seed(\Database\Seeders\StudentClassTestSeeder::class);

        $student = Student::first();
        $lecturer = User::where('access_code', 'LienNTD2')->first();
        $admin = User::factory()->create([
            'access_code' => 'ADMIN01',
            'full_name' => 'Admin Test',
            'role_label' => 'Admin',
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        return [$student, $lecturer, $admin];
    }

    public function test_analytics_page_renders_for_lecturer(): void
    {
        [, $lecturer] = $this->seedContext();

        $this->actingAs($lecturer)
            ->get(route('analytics'))
            ->assertOk()
            ->assertSee('Độ bao phủ Feedback');
    }

    public function test_report_page_shows_feedback_rows(): void
    {
        [$student, $lecturer] = $this->seedContext();

        Feedback::create([
            'student_id' => $student->id,
            'author_access_code' => $lecturer->access_code,
            'author_name' => $lecturer->full_name,
            'author_role' => $lecturer->role,
            'content' => 'Sinh viên cần theo dõi thêm',
        ]);

        $this->actingAs($lecturer)
            ->get(route('reports', ['generate' => 1]))
            ->assertOk()
            ->assertSee('Sinh viên cần theo dõi thêm')
            ->assertSee($student->student_code);
    }

    public function test_admin_can_toggle_user_and_import_requires_csv(): void
    {
        [, $lecturer, $admin] = $this->seedContext();

        $this->actingAs($admin)
            ->get(route('admin.users'))
            ->assertOk()
            ->assertSee('Quản lý tài khoản');

        $this->actingAs($admin)
            ->patch(route('admin.users.toggle', $lecturer->access_code))
            ->assertRedirect();

        $lecturer->refresh();
        $this->assertFalse($lecturer->is_active);

        $this->actingAs($admin)
            ->post(route('admin.roster.import'), [
                'file' => UploadedFile::fake()->create('roster.pdf', 100),
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_user_can_submit_bug_report(): void
    {
        [, $lecturer] = $this->seedContext();

        $this->actingAs($lecturer)
            ->post(route('bug-reports.store'), [
                'content' => 'Giao diện bị lỗi khi mở modal trên mobile',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('bug_reports', [
            'author_access_code' => $lecturer->access_code,
            'status' => 'open',
        ]);
    }

    public function test_admin_can_resolve_bug_report(): void
    {
        [, $lecturer, $admin] = $this->seedContext();

        $report = BugReport::create([
            'author_access_code' => $lecturer->access_code,
            'author_name' => $lecturer->full_name,
            'content' => 'Lỗi hiển thị timeline',
            'status' => 'open',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.bug-reports.resolve', $report))
            ->assertRedirect();

        $this->assertDatabaseHas('bug_reports', [
            'id' => $report->id,
            'status' => 'resolved',
        ]);
    }

    public function test_pending_escalation_appears_in_action_items(): void
    {
        [$student, $lecturer] = $this->seedContext();

        Feedback::create([
            'student_id' => $student->id,
            'author_access_code' => $lecturer->access_code,
            'author_name' => $lecturer->full_name,
            'author_role' => $lecturer->role,
            'content' => 'Cần CTSV hỗ trợ gấp về tình trạng nghỉ học',
            'requires_escalation' => true,
        ]);

        $data = app(AnalyticsService::class)->dashboard($lecturer);

        $this->assertSame(1, $data['kpis']['pending_escalations']);
        $this->assertCount(1, $data['actions']['pending_escalations']);
        $this->assertSame($student->id, $data['actions']['pending_escalations'][0]['student_id']);
    }

    public function test_high_absence_stable_student_flagged(): void
    {
        [$student, $lecturer] = $this->seedContext();

        $student->update(['care_status' => CareStatus::Stable]);
        StudentClass::where('student_id', $student->id)->delete();
        StudentClass::create([
            'student_id' => $student->id,
            'lecturer_id' => $lecturer->id,
            'class_name' => 'CLASS-X',
            'subject_code' => 'SUBX',
            'faculty' => 'Business',
            'semester' => 'Summer 2026',
            'absence_rate' => 25,
        ]);

        $data = app(AnalyticsService::class)->dashboard($lecturer);

        $codes = array_column($data['actions']['high_absence_stable'], 'student_code');
        $this->assertContains($student->student_code, $codes);
    }

    public function test_faculty_chart_counts_unique_students_not_enrollments(): void
    {
        [, $lecturer] = $this->seedContext();

        $student = Student::first();
        $student->update(['care_status' => CareStatus::Critical]);

        StudentClass::where('student_id', $student->id)->delete();

        for ($i = 1; $i <= 5; $i++) {
            StudentClass::create([
                'student_id' => $student->id,
                'lecturer_id' => $lecturer->id,
                'class_name' => "CLASS-{$i}",
                'subject_code' => "SUB{$i}",
                'faculty' => 'Business',
                'semester' => 'Summer 2026',
            ]);
        }

        $chartData = app(AnalyticsService::class)->dashboard($lecturer);
        $idx = array_search('Business', $chartData['by_faculty']['labels'], true);

        $this->assertNotFalse($idx);
        $this->assertSame(1, $chartData['by_faculty']['critical'][$idx]);
    }

    public function test_non_admin_cannot_access_admin_panel(): void
    {
        [, $lecturer] = $this->seedContext();

        $this->actingAs($lecturer)
            ->get(route('admin.users'))
            ->assertForbidden();
    }

    public function test_admin_students_page_filters_and_paginates(): void
    {
        [$student, , $admin] = $this->seedContext();

        $this->actingAs($admin)
            ->get(route('admin.students', ['care_status' => 'critical', 'search' => $student->student_code]))
            ->assertOk()
            ->assertSee('Danh sách sinh viên');
    }

    public function test_admin_bug_reports_page_renders(): void
    {
        [, $lecturer, $admin] = $this->seedContext();

        BugReport::create([
            'author_access_code' => $lecturer->access_code,
            'author_name' => $lecturer->full_name,
            'content' => 'Một góp ý cần xem xét sớm',
            'status' => 'open',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.bug-reports', ['status' => 'open']))
            ->assertOk()
            ->assertSee('Một góp ý cần xem xét sớm');
    }

    public function test_dashboard_search_ignores_status_filter(): void
    {
        [$student, $lecturer] = $this->seedContext();

        $student->update(['care_status' => 'stable']);

        $monitoringStudent = Student::create([
            'student_code' => 'BC00999',
            'full_name' => 'Monitoring Student',
            'care_status' => 'monitoring',
        ]);

        StudentClass::create([
            'student_id' => $monitoringStudent->id,
            'lecturer_id' => $lecturer->id,
            'class_name' => 'ENT4011.01',
            'subject_code' => 'ENT4011',
            'faculty' => 'English',
            'semester' => 'Summer 2026',
        ]);

        $this->actingAs($lecturer)
            ->get(route('dashboard', ['status' => 'yellow', 'search' => $student->student_code]))
            ->assertOk()
            ->assertSee($student->student_code)
            ->assertDontSee($monitoringStudent->student_code);

        $response = $this->actingAs($lecturer)
            ->getJson(route('dashboard.data', ['status' => 'yellow', 'search' => $student->student_code]))
            ->assertOk();

        $this->assertStringContainsString($student->student_code, $response->json('students'));
    }

    public function test_admin_can_delete_student_data_from_bug_reports_page(): void
    {
        [$student, $lecturer, $admin] = $this->seedContext();

        $feedback = Feedback::create([
            'student_id' => $student->id,
            'author_access_code' => $lecturer->access_code,
            'author_name' => $lecturer->full_name,
            'author_role' => $lecturer->role,
            'content' => 'Giữ lại để kiểm tra xoá dữ liệu',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.bug-reports.student-data.destroy'))
            ->assertRedirect();

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
        $this->assertDatabaseMissing('feedbacks', ['id' => $feedback->id]);
        $this->assertDatabaseCount('students', 0);
    }
}
