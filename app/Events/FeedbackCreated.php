<?php

namespace App\Events;

use App\Models\Feedback;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FeedbackCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Feedback $feedback
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('student.'.$this->feedback->student_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'FeedbackCreated';
    }

    public function broadcastWith(): array
    {
        $this->feedback->loadMissing('student');

        return [
            'feedback' => [
                'id' => $this->feedback->id,
                'student_id' => $this->feedback->student_id,
                'parent_id' => $this->feedback->parent_id,
                'author_access_code' => $this->feedback->author_access_code,
                'author_name' => $this->feedback->author_name,
                'author_role' => $this->feedback->author_role->value,
                'content' => $this->feedback->content,
                'requires_escalation' => $this->feedback->requires_escalation,
                'created_at' => $this->feedback->created_at?->toIso8601String(),
            ],
        ];
    }
}
