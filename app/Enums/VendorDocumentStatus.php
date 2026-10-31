<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum VendorDocumentStatus: string
{
    use HasOptions;

    case ActionNeeded = 'action_needed';
    case ExpiringSoon = 'expiring_soon';
    case UpToDate = 'up_to_date';

    public function label(): string
    {
        return match ($this) {
            self::ActionNeeded => 'Action needed',
            self::ExpiringSoon => 'Expiring soon',
            self::UpToDate => 'Up to date',
        };
    }

    /**
     * The position in a list sorted by what needs attention first.
     */
    public function priority(): int
    {
        return match ($this) {
            self::ActionNeeded => 0,
            self::ExpiringSoon => 1,
            self::UpToDate => 2,
        };
    }
}
