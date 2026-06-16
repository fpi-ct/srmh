<?php

namespace App\Support;

class AbsenceRateUi
{
    public const WARNING_MIN = 10;

    public const WARNING_MAX = 17;

    public const DANGER_MIN = 20;

    public static function level(?float $rate): string
    {
        if ($rate === null || $rate <= 0) {
            return 'normal';
        }

        if ($rate >= self::DANGER_MIN) {
            return 'danger';
        }

        if ($rate >= self::WARNING_MIN) {
            return 'warning';
        }

        return 'normal';
    }

    public static function badgeClass(?float $rate): string
    {
        return match (self::level($rate)) {
            'danger' => 'bg-rose-100 text-rose-700',
            'warning' => 'bg-amber-100 text-amber-700',
            default => 'bg-slate-100 text-slate-600',
        };
    }

    public static function isAtRisk(?float $rate): bool
    {
        return self::level($rate) !== 'normal';
    }
}
