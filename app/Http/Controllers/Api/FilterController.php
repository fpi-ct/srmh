<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FilterController extends Controller
{
    public function __construct(
        private StudentService $studentService
    ) {}

    public function classes(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->studentService->availableClasses($request->user()),
        ]);
    }

    public function faculties(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->studentService->availableFaculties($request->user()),
        ]);
    }
}
