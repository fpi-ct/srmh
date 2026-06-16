<?php

namespace App\Http\Controllers\Web;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Services\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private StudentService $studentService
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        if (! $request->has('status')) {
            return redirect()->route('dashboard', array_merge(
                $request->query(),
                ['status' => 'yellow']
            ));
        }

        return view('dashboard.index', array_merge(
            ['activeTab' => 'dashboard'],
            $this->dashboardViewData($request)
        ));
    }

    public function data(Request $request): JsonResponse
    {
        if (! $request->has('status')) {
            $request->merge(['status' => 'yellow']);
        }

        $data = $this->dashboardViewData($request);

        return response()->json([
            'stats' => view('dashboard.partials.stats', $data)->render(),
            'students' => view('dashboard.partials.student-list', $data)->render(),
            'pagination' => view('dashboard.partials.pagination', $data)->render(),
        ]);
    }

    private function dashboardViewData(Request $request): array
    {
        $user = $request->user();
        $filters = $request->only(['care_status', 'class_section', 'faculty', 'search']);

        if ($request->filled('status')) {
            $legacy = $request->string('status')->toString();
            $filters['care_status'] = match ($legacy) {
                'green' => 'stable',
                'yellow' => 'monitoring',
                'red' => 'critical',
                default => null,
            };
        }

        $students = $this->studentService->list($user, array_filter($filters));
        $students->load([
            'studentClasses.lecturer',
            'feedbacks' => fn ($q) => $q->latest()->limit(1),
        ]);

        $students = $this->studentService->sortForDashboard($students);

        $perPage = 50;
        $page = max(1, (int) $request->get('page', 1));
        $total = $students->count();
        $paginated = $students->slice(($page - 1) * $perPage, $perPage)->values();

        return [
            'stats' => $this->studentService->stats($user),
            'students' => $paginated,
            'classes' => $this->studentService->availableClasses($user),
            'faculties' => $this->studentService->availableFaculties($user),
            'filters' => $request->all(),
            'showFacultyFilter' => in_array($user->role, [UserRole::Admin, UserRole::StudentAffairs], true),
            'page' => $page,
            'perPage' => $perPage,
            'totalStudents' => $total,
            'lastPage' => (int) ceil($total / $perPage),
        ];
    }
}
