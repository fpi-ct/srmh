<?php

namespace App\Events;

use App\Models\Feedback;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FeedbackReacted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Feedback $feedback,
        public int $count,
        public string $actorAccessCode
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('student.'.$this->feedback->student_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'FeedbackReacted';
    }

    public function broadcastWith(): array
    {
        return [
            'feedback_id' => $this->feedback->id,
            'count' => $this->count,
            'actor' => $this->actorAccessCode,
        ];
    }
}
