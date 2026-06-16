<?php

namespace App\Enums;

enum NotificationType: string
{
    case StatusCritical = 'status_critical';
    case Escalation = 'escalation';
    case NewFeedback = 'new_feedback';
    case Reply = 'reply';
}
