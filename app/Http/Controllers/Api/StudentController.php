<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Services\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StudentController extends Controller
{
    public function __construct(
        private StudentService $studentService
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Student::class);

        $students = $this->studentService->list($request->user(), $request->only([
            'care_status', 'class_section', 'faculty', 'search',
        ]));

        return StudentResource::collection($students);
    }

    public function stats(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Student::class);

        return response()->json(
            $this->studentService->stats($request->user())
        );
    }

    public function show(Request $request, Student $student): StudentResource|JsonResponse
    {
        $this->authorize('view', $student);

        $student->load('rosterSummary');

        return new StudentResource($student);
    }
}
