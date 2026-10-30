<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum BudgetRange: string
{
    use HasOptions;

    case Under500 = 'under_500';
    case From500To2000 = '500_2000';
    case From2000To5000 = '2000_5000';
    case From5000To15000 = '5000_15000';
    case From15000To50000 = '15000_50000';
    case Over50000 = 'over_50000';
    case Unsure = 'unsure';

    public function label(): string
    {
        return match ($this) {
            self::Under500 => 'Under $500',
            self::From500To2000 => '$500 – $2,000',
            self::From2000To5000 => '$2,000 – $5,000',
            self::From5000To15000 => '$5,000 – $15,000',
            self::From15000To50000 => '$15,000 – $50,000',
            self::Over50000 => 'Over $50,000',
            self::Unsure => 'Not sure yet',
        };
    }
}
