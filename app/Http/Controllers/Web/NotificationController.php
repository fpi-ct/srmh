<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $items = $this->notificationService->listForUser($request->user());

        return response()->json([
            'items' => $items->map(fn (AppNotification $n) => $this->format($n)),
            'unread_count' => $items->count(),
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $this->notificationService->unreadCount($request->user()),
        ]);
    }

    public function markRead(Request $request, AppNotification $notification): JsonResponse
    {
        $this->notificationService->markRead($request->user(), $notification);

        return response()->json([
            'ok' => true,
            'unread_count' => $this->notificationService->unreadCount($request->user()),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $this->notificationService->markAllRead($request->user());

        return response()->json(['ok' => true, 'unread_count' => 0]);
    }

    private function format(AppNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type->value,
            'title' => $notification->title,
            'body' => $notification->body,
            'data' => $notification->data,
            'created_at' => $notification->created_at?->toIso8601String(),
            'care_status' => $notification->data['care_status'] ?? 'stable',
            'student_id' => $notification->data['student_id'] ?? null,
            'student_code' => $notification->data['student_code'] ?? null,
            'student_name' => $notification->data['student_name'] ?? null,
        ];
    }
}
