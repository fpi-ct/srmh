<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait RespondsForStudentModal
{
    protected function modalOrBack(Request $request, ?string $message = null): JsonResponse|RedirectResponse
    {
        if ($request->header('X-SRMH-Modal')) {
            return response()->json([
                'ok' => true,
                'message' => $message,
            ]);
        }

        return $message
            ? back()->with('success', $message)
            : back();
    }
}
