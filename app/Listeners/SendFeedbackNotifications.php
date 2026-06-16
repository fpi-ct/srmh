<?php

namespace App\Listeners;

use App\Events\FeedbackCreated;
use App\Services\NotificationService;

class SendFeedbackNotifications
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function handle(FeedbackCreated $event): void
    {
        $this->notificationService->notifyForFeedback($event->feedback);
    }
}
