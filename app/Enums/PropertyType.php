<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum PropertyType: string
{
    use HasOptions;

    case House = 'house';
    case Townhouse = 'townhouse';
    case Apartment = 'apartment';
    case Commercial = 'commercial';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::House => 'House',
            self::Townhouse => 'Townhouse / unit',
            self::Apartment => 'Apartment',
            self::Commercial => 'Commercial',
            self::Other => 'Other',
        };
    }
}
