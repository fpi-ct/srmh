<?php

namespace App\Events;

use App\Models\Feedback;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FeedbackAgreed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<int, string>  $agreeNames
     */
    public function __construct(
        public Feedback $parent,
        public array $agreeNames,
        public string $actorAccessCode
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('student.'.$this->parent->student_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'FeedbackAgreed';
    }

    public function broadcastWith(): array
    {
        return [
            'feedback_id' => $this->parent->id,
            'agree_names' => $this->agreeNames,
            'actor' => $this->actorAccessCode,
        ];
    }
}
