<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FilterController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Middleware\EnsureUserActive;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');
});

Route::middleware(['auth:sanctum', EnsureUserActive::class])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });

    Route::get('students/stats', [StudentController::class, 'stats']);
    Route::get('students', [StudentController::class, 'index']);
    Route::get('students/{student}', [StudentController::class, 'show']);

    Route::prefix('filters')->group(function () {
        Route::get('classes', [FilterController::class, 'classes']);
        Route::get('faculties', [FilterController::class, 'faculties']);
    });
});
