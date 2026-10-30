<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum JobCategory: string
{
    use HasOptions;

    case Plumbing = 'plumbing';
    case Electrical = 'electrical';
    case Carpentry = 'carpentry';
    case Painting = 'painting';
    case Plastering = 'plastering';
    case Tiling = 'tiling';
    case Roofing = 'roofing';
    case Flooring = 'flooring';
    case Fencing = 'fencing';
    case Landscaping = 'landscaping';
    case KitchenRenovation = 'kitchen_renovation';
    case BathroomRenovation = 'bathroom_renovation';
    case GeneralHandyman = 'general_handyman';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Plumbing => 'Plumbing',
            self::Electrical => 'Electrical',
            self::Carpentry => 'Carpentry',
            self::Painting => 'Painting',
            self::Plastering => 'Plastering',
            self::Tiling => 'Tiling',
            self::Roofing => 'Roofing & guttering',
            self::Flooring => 'Flooring',
            self::Fencing => 'Fencing & gates',
            self::Landscaping => 'Landscaping & gardening',
            self::KitchenRenovation => 'Kitchen renovation',
            self::BathroomRenovation => 'Bathroom renovation',
            self::GeneralHandyman => 'General handyman',
            self::Other => 'Other',
        };
    }
}
