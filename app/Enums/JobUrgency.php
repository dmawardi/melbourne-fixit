<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum JobUrgency: string
{
    use HasOptions;

    case Emergency = 'emergency';
    case WithinAWeek = 'within_a_week';
    case WithinAMonth = 'within_a_month';
    case Flexible = 'flexible';

    public function label(): string
    {
        return match ($this) {
            self::Emergency => 'Emergency (ASAP)',
            self::WithinAWeek => 'Within a week',
            self::WithinAMonth => 'Within a month',
            self::Flexible => 'Flexible',
        };
    }
}
