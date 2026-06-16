<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportRosterRequest;
use App\Services\FapImportService;
use Illuminate\Http\RedirectResponse;

class RosterImportController extends Controller
{
    public function __construct(
        private FapImportService $fapImportService
    ) {}

    public function store(ImportRosterRequest $request): RedirectResponse
    {
        try {
            $result = $this->fapImportService->importFromFile(
                $request->file('file')->getRealPath()
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'Import thất bại: '.$e->getMessage());
        }

        return back()->with('success', sprintf(
            'Import thành công kỳ %s: %d sinh viên, %d dòng lớp.',
            $result['semester'],
            $result['students'],
            $result['student_classes']
        ));
    }
}
