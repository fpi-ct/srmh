<?php

namespace App\Support;

use App\Enums\CareStatus;

class CareStatusUi
{
    public static function legacyKey(CareStatus $status): string
    {
        return match ($status) {
            CareStatus::Stable => 'green',
            CareStatus::Monitoring => 'yellow',
            CareStatus::Critical => 'red',
        };
    }

    public static function strip(CareStatus $status): string
    {
        return match ($status) {
            CareStatus::Stable => 'strip-green',
            CareStatus::Monitoring => 'strip-yellow',
            CareStatus::Critical => 'strip-red',
        };
    }

    public static function badge(CareStatus $status): string
    {
        return match ($status) {
            CareStatus::Stable => 'bg-emerald-100 text-emerald-700',
            CareStatus::Monitoring => 'bg-amber-100 text-amber-700',
            CareStatus::Critical => 'bg-rose-100 text-rose-700',
        };
    }

    public static function label(CareStatus $status): string
    {
        return match ($status) {
            CareStatus::Stable => 'Ổn định',
            CareStatus::Monitoring => 'Theo dõi',
            CareStatus::Critical => 'Cảnh báo',
        };
    }

    public static function emoji(CareStatus $status): string
    {
        return match ($status) {
            CareStatus::Stable => '🟢',
            CareStatus::Monitoring => '🟡',
            CareStatus::Critical => '🔴',
        };
    }
}
