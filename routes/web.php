<?php

use App\Http\Controllers\Web\Admin\BugReportController as AdminBugReportController;
use App\Http\Controllers\Web\Admin\FapSyncGuideController;
use App\Http\Controllers\Web\Admin\RosterImportController;
use App\Http\Controllers\Web\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Web\Admin\UserController as AdminUserController;
use App\Http\Controllers\Web\AnalyticsController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\BugReportController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\FeedbackController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\PushSubscriptionController;
use App\Http\Controllers\Web\ReportController;
use App\Http\Controllers\Web\StudentController;
use App\Http\Middleware\EnsureUserActive;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
});

Route::middleware(['auth', EnsureUserActive::class])->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports');
    Route::post('/bug-reports', [BugReportController::class, 'store'])->name('bug-reports.store');
    Route::get('/students/{student}', [StudentController::class, 'show'])->name('students.show');
    Route::get('/students/{student}/panel', [StudentController::class, 'panel'])->name('students.panel');
    Route::patch('/students/{student}/care-status', [StudentController::class, 'updateCareStatus'])->name('students.care-status');
    Route::post('/students/{student}/feedbacks', [FeedbackController::class, 'store'])->name('students.feedbacks.store');
    Route::post('/students/{student}/feedbacks/{feedback}/react', [FeedbackController::class, 'react'])->name('feedbacks.react');
    Route::post('/students/{student}/feedbacks/{feedback}/agree', [FeedbackController::class, 'agree'])->name('feedbacks.agree');
    Route::post('/students/{student}/feedbacks/{feedback}/escalate', [FeedbackController::class, 'escalate'])->name('feedbacks.escalate');
    Route::post('/students/{student}/feedbacks/{feedback}/resolve', [FeedbackController::class, 'resolve'])->name('feedbacks.resolve');
    Route::get('/students/{student}/feedbacks/{feedback}/item', [FeedbackController::class, 'item'])->name('feedbacks.item');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    Route::post('/push/subscribe', [PushSubscriptionController::class, 'store'])->name('push.subscribe');
    Route::delete('/push/unsubscribe', [PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');

    Route::middleware(EnsureUserIsAdmin::class)->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])->name('users');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::patch('/users/{access_code}/toggle', [AdminUserController::class, 'toggle'])->name('users.toggle');
        Route::post('/roster/import', [RosterImportController::class, 'store'])->name('roster.import');
        Route::get('/students', [AdminStudentController::class, 'index'])->name('students');
        Route::get('/fap-sync-guide', [FapSyncGuideController::class, 'index'])->name('fap-sync-guide');
        Route::get('/bug-reports', [AdminBugReportController::class, 'index'])->name('bug-reports');
        Route::patch('/bug-reports/{bugReport}/resolve', [AdminBugReportController::class, 'resolve'])->name('bug-reports.resolve');
        Route::delete('/bug-reports/student-data', [AdminBugReportController::class, 'destroyStudentData'])->name('bug-reports.student-data.destroy');
    });

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
