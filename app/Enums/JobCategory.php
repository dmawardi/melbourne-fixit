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

    /**
     * A short public description of the service.
     */
    public function description(): string
    {
        return match ($this) {
            self::Plumbing => 'Leaking taps, blocked drains, hot water systems, toilets and new fixtures.',
            self::Electrical => 'Power points, lighting, switchboards, ceiling fans and safety switches.',
            self::Carpentry => 'Doors, decks, pergolas, shelving, skirting and structural repairs.',
            self::Painting => 'Interior and exterior painting, from a single room to the whole house.',
            self::Plastering => 'Patching holes and cracks, new plasterboard, cornices and ceilings.',
            self::Tiling => 'Floor and wall tiling, regrouting and replacing cracked tiles.',
            self::Roofing => 'Roof leaks, broken tiles, gutter cleaning and gutter replacement.',
            self::Flooring => 'Timber, laminate, vinyl and hybrid flooring, plus sanding and polishing.',
            self::Fencing => 'New fences, repairs, gates and retaining walls.',
            self::Landscaping => 'Garden clean-ups, paving, turf, planting and irrigation.',
            self::KitchenRenovation => 'Full kitchen renovations or updates to benchtops, cabinets and splashbacks.',
            self::BathroomRenovation => 'Full bathroom renovations, waterproofing, vanities and showers.',
            self::GeneralHandyman => 'Odd jobs and small repairs: hanging, assembling, adjusting and fixing.',
            self::Other => 'Something else around the home? Describe it and we\'ll find the right trade.',
        };
    }

    /**
     * Get the services to advertise publicly, each with a description.
     *
     * @return list<array{value: string, label: string, description: string}>
     */
    public static function services(): array
    {
        $services = [];

        foreach (self::cases() as $case) {
            if ($case !== self::Other) {
                $services[] = [...$case->toOption(), 'description' => $case->description()];
            }
        }

        return $services;
    }
}
