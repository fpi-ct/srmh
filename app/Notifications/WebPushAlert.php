<?php

namespace App\Notifications;

use App\Models\AppNotification;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class WebPushAlert extends Notification
{

    public function __construct(
        private AppNotification $appNotification
    ) {}

    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        $studentId = $this->appNotification->data['student_id'] ?? null;
        $url = $studentId ? url('/dashboard?student='.$studentId) : url('/dashboard');

        return (new WebPushMessage)
            ->title($this->appNotification->title)
            ->body($this->appNotification->body)
            ->icon('/icons/icon-192.png')
            ->badge('/icons/badge-72.png')
            ->data(['url' => $url])
            ->options(['TTL' => 3600]);
    }
}
