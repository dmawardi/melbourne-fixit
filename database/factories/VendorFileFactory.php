<?php

namespace Database\Factories;

use App\Enums\VendorDocument;
use App\Models\Vendor;
use App\Models\VendorFile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<VendorFile>
 */
class VendorFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory(),
            'uploaded_by' => null,
            'document' => VendorDocument::PublicLiability,
            'disk' => config('filesystems.vendor_documents'),
            'path' => fn (array $attributes) => "vendors/{$attributes['vendor_id']}/".Str::lower((string) Str::ulid()).'.pdf',
            'original_name' => 'certificate-of-currency-'.fake()->numerify('####').'.pdf',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(50_000, 500_000),
            'uploaded_at' => now(),
        ];
    }
}
