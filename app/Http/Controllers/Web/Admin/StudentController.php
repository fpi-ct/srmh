<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\CareStatus;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentClass;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->get('care_status');
        $faculty = $request->get('faculty');
        $classSection = $request->get('class_section');
        $search = trim((string) $request->get('search'));

        $query = Student::query()
            ->with('studentClasses')
            ->withCount('feedbacks');

        if ($status && CareStatus::tryFrom($status)) {
            $query->where('care_status', $status);
        }

        if ($faculty) {
            $query->whereHas('studentClasses', fn ($q) => $q->where('faculty', $faculty));
        }

        if ($classSection) {
            $query->whereHas('studentClasses', fn ($q) => $q->where('class_name', $classSection));
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('student_code', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%");
            });
        }

        return view('admin.students.index', [
            'activeTab' => 'admin',
            'adminSection' => 'students',
            'students' => $query->orderBy('full_name')->paginate(20)->withQueryString(),
            'faculties' => StudentClass::query()->distinct()->orderBy('faculty')->pluck('faculty')->filter()->values(),
            'classes' => StudentClass::query()->distinct()->orderBy('class_name')->pluck('class_name')->filter()->values(),
            'status' => $status,
            'faculty' => $faculty,
            'classSection' => $classSection,
            'search' => $search,
        ]);
    }
}
