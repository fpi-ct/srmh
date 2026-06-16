<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Services\StudentService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reportService,
        private StudentService $studentService
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $classSection = $request->get('class_section', 'all');
        $generated = $request->boolean('generate');

        return view('reports.index', [
            'activeTab' => 'dashboard',
            'subtitle' => $this->reportService->subtitle($user),
            'classes' => $this->studentService->availableClasses($user),
            'classSection' => $classSection,
            'generated' => $generated,
            'rows' => $generated ? $this->reportService->rows($user, $classSection) : collect(),
            'reportService' => $this->reportService,
        ]);
    }
}
