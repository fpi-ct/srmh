<?php

namespace App\Enums;

enum CareStatus: string
{
    case Stable = 'stable';
    case Monitoring = 'monitoring';
    case Critical = 'critical';
}
