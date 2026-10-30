<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum Visibility: string
{
    use HasOptions;

    case Internal = 'internal';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::Internal => 'Admin only',
            self::Customer => 'Shared with customer',
        };
    }
}
