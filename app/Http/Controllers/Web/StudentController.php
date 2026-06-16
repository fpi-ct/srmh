<?php

namespace App\Http\Controllers\Web;

use App\Enums\UserRole;
use App\Http\Controllers\Concerns\RespondsForStudentModal;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCareStatusRequest;
use App\Models\Student;
use App\Services\FeedbackService;
use App\Enums\CareStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    use RespondsForStudentModal;

    public function __construct(
        private FeedbackService $feedbackService
    ) {}

    public function show(Request $request, Student $student): RedirectResponse
    {
        $this->authorize('view', $student);

        return redirect()->route('dashboard', array_merge(
            $request->only(['status', 'search', 'faculty', 'class_section', 'page']),
            ['student' => $student->id]
        ));
    }

    public function panel(Request $request, Student $student): View
    {
        $this->authorize('view', $student);

        return view('students.partials.modal-panel', $this->studentViewData($request, $student));
    }

    public function updateCareStatus(UpdateCareStatusRequest $request, Student $student): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $student);

        $this->feedbackService->updateCareStatus(
            $request->user(),
            $student,
            CareStatus::from($request->string('care_status')->toString()),
            $request->string('reason')->toString()
        );

        return $this->modalOrBack($request, 'Đã cập nhật trạng thái chăm sóc.');
    }

    private function studentViewData(Request $request, Student $student): array
    {
        $student->load([
            'studentClasses.lecturer',
            'feedbacks' => fn ($q) => $q
                ->where('is_agreed_duplicate', false)
                ->whereNull('parent_id')
                ->with([
                    'replies' => fn ($r) => $r->orderBy('created_at'),
                    'reactions',
                    'agrees',
                ])
                ->orderBy('created_at'),
        ]);

        $classes = $student->studentClasses;
        $user = $request->user();

        return [
            'student' => $student,
            'classNames' => $classes->pluck('class_name')->unique()->implode(', '),
            'faculties' => $classes->pluck('faculty')->unique()->implode(', '),
            'canChangeStatus' => in_array($user->role, [UserRole::Admin, UserRole::StudentAffairs, UserRole::DepartmentHead, UserRole::Lecturer], true),
            'canEscalate' => in_array($user->role, [UserRole::Lecturer, UserRole::DepartmentHead], true),
        ];
    }
}
