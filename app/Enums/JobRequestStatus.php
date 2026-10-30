<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum JobRequestStatus: string
{
    use HasOptions;

    case Submitted = 'submitted';
    case Reviewing = 'reviewing';
    case Quoted = 'quoted';
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::Reviewing => 'Under review',
            self::Quoted => 'Quoted',
            self::Scheduled => 'Scheduled',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Whether the customer can still edit the request and its photos.
     */
    public function isEditable(): bool
    {
        return in_array($this, [self::Submitted, self::Reviewing], true);
    }
}
