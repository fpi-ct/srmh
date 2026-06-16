<?php

namespace App\Events;

use App\Models\AppNotification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public AppNotification $notification
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->notification->user_access_code),
        ];
    }

    public function broadcastAs(): string
    {
        return 'NotificationSent';
    }

    public function broadcastWith(): array
    {
        $data = $this->notification->data ?? [];

        return [
            'notification' => [
                'id' => $this->notification->id,
                'type' => $this->notification->type->value,
                'title' => $this->notification->title,
                'body' => $this->notification->body,
                'data' => $data,
                'created_at' => $this->notification->created_at?->toIso8601String(),
                'care_status' => $data['care_status'] ?? 'stable',
                'student_id' => $data['student_id'] ?? null,
                'student_code' => $data['student_code'] ?? null,
                'student_name' => $data['student_name'] ?? null,
            ],
        ];
    }
}
