<?php

namespace App\Enums;

enum NotificationType: string
{
    case StatusCritical = 'status_critical';
    case StatusMonitoring = 'status_monitoring';
    case Escalation = 'escalation';
    case NewFeedback = 'new_feedback';
    case Reply = 'reply';
}
