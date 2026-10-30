<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum VendorType: string
{
    use HasOptions;

    case General = 'general';
    case Specialist = 'specialist';

    public function label(): string
    {
        return match ($this) {
            self::General => 'General (all job types)',
            self::Specialist => 'Specialist (selected job types)',
        };
    }
}
