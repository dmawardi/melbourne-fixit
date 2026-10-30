<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum FileKind: string
{
    use HasOptions;

    case Photo = 'photo';
    case VendorQuote = 'vendor_quote';
    case ClientQuote = 'client_quote';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Photo => 'Photo',
            self::VendorQuote => 'Vendor quote',
            self::ClientQuote => 'Client quote',
            self::Other => 'Other',
        };
    }
}
