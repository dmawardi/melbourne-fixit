<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

/**
 * The documents to collect from a vendor. The guidance reflects Victorian regulator advice as at
 * October 2026, so review it when thresholds or regulators change.
 */
enum VendorDocument: string
{
    use HasOptions;

    case Business = 'business';
    case PublicLiability = 'public_liability';
    case WorkCover = 'workcover';
    case BuilderRegistration = 'builder_registration';
    case PlumbingLicence = 'plumbing_licence';
    case ElectricalRegistration = 'electrical_registration';

    public function label(): string
    {
        return match ($this) {
            self::Business => 'Business details',
            self::PublicLiability => 'Public liability insurance',
            self::WorkCover => 'WorkCover insurance',
            self::BuilderRegistration => 'Domestic builder registration',
            self::PlumbingLicence => 'Plumbing registration or licence',
            self::ElectricalRegistration => 'Registered electrical contractor',
        };
    }

    /**
     * What the admin needs to collect or check, and when it applies.
     */
    public function guidance(): string
    {
        return match ($this) {
            self::Business => 'Record the ABN and check it is active on ABN Lookup.',
            self::PublicLiability => 'Get a current certificate of currency. We tell customers every tradesperson is insured, so every vendor needs this. Electrical contractors must hold at least $5 million in cover.',
            self::WorkCover => 'If the vendor employs workers, get a WorkCover certificate of currency. Check too whether a contractor could count as your own worker, as that can affect your WorkCover obligations.',
            self::BuilderRegistration => 'Check registration with the Building and Plumbing Commission. It is needed for domestic building work over $10,000 or needing a building permit, unless the work involves only one listed trade, such as plastering, tiling, painting, plumbing, electrical or floor coverings. Combining trades can trigger it. Work over $10,000 needs a major domestic building contract, and contracts from 1 July 2026 for work over $20,000 need Home Warranty insurance.',
            self::PlumbingLicence => 'Plumbing, gasfitting and drainage work must be done by someone registered or licensed with the Building and Plumbing Commission. Ask to see their card and check it covers the type of work, including roofing, gutters or irrigation. Customers must get a compliance certificate for work of $750 or more.',
            self::ElectricalRegistration => 'A business contracting for electrical work must be registered with Energy Safe Victoria. Check the number on the public register, and confirm at least $5 million in public liability cover. Customers must get a Certificate of Electrical Safety for electrical installation work.',
        };
    }

    /**
     * Where to verify the document or read the official guidance.
     */
    public function url(): string
    {
        return match ($this) {
            self::Business => 'https://abr.business.gov.au',
            self::BuilderRegistration => 'https://www.consumer.vic.gov.au/housing/building-and-renovating/plan-and-manage-your-building-project/about-builders-tradespeople-and-other-building-practitioners',
            self::WorkCover => 'https://www.worksafe.vic.gov.au/request-certificate-currency-your-workcover-insurance',
            self::PlumbingLicence => 'https://www.bpc.vic.gov.au',
            self::PublicLiability, self::ElectricalRegistration => 'https://www.energysafe.vic.gov.au/licensing/electrical-licences/licence-types/registered-electrical-contractors-rec',
        };
    }

    /**
     * The column holding the document's number.
     */
    public function numberColumn(): string
    {
        return match ($this) {
            self::Business => 'abn',
            self::PublicLiability => 'public_liability_policy_number',
            self::WorkCover => 'workcover_policy_number',
            self::BuilderRegistration => 'builder_registration_number',
            self::PlumbingLicence => 'plumbing_licence_number',
            self::ElectricalRegistration => 'electrical_registration_number',
        };
    }

    /**
     * The column holding the document's expiry date, if it has one.
     */
    public function expiryColumn(): ?string
    {
        return match ($this) {
            self::Business => null,
            self::PublicLiability => 'public_liability_expires_on',
            self::WorkCover => 'workcover_expires_on',
            self::BuilderRegistration => 'builder_registration_expires_on',
            self::PlumbingLicence => 'plumbing_licence_expires_on',
            self::ElectricalRegistration => 'electrical_registration_expires_on',
        };
    }

    /**
     * The job categories that call for the document. Null means every vendor needs it.
     *
     * @return list<JobCategory>|null
     */
    public function categories(): ?array
    {
        return match ($this) {
            self::Business, self::PublicLiability, self::WorkCover, self::BuilderRegistration => null,
            self::PlumbingLicence => [
                JobCategory::Plumbing,
                JobCategory::Roofing,
                JobCategory::Landscaping,
                JobCategory::KitchenRenovation,
                JobCategory::BathroomRenovation,
            ],
            self::ElectricalRegistration => [
                JobCategory::Electrical,
                JobCategory::KitchenRenovation,
                JobCategory::BathroomRenovation,
            ],
        };
    }

    /**
     * Get the documents with their guidance, for the vendor form.
     *
     * @return list<array{value: string, label: string, guidance: string, url: string, categories: list<string>|null}>
     */
    public static function requirements(): array
    {
        return array_map(fn (self $case): array => [
            ...$case->toOption(),
            'guidance' => $case->guidance(),
            'url' => $case->url(),
            'categories' => $case->categories() === null
                ? null
                : array_map(fn (JobCategory $category): string => $category->value, $case->categories()),
        ], self::cases());
    }
}
