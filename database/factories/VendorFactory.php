<?php

namespace Database\Factories;

use App\Enums\JobCategory;
use App\Enums\VendorType;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vendor>
 */
class VendorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'type' => VendorType::General,
            'categories' => null,
            'contact_name' => fake()->name(),
            'email' => fake()->companyEmail(),
            'phone' => fake()->numerify('04## ### ###'),
            'notes' => null,
        ];
    }

    /**
     * Indicate that the vendor has supplied its business and insurance documents.
     */
    public function withDocuments(): static
    {
        return $this->state(fn (array $attributes) => [
            'abn' => fake()->numerify('## ### ### ###'),
            'public_liability_insurer' => fake()->company(),
            'public_liability_policy_number' => fake()->bothify('PL-#######'),
            'public_liability_cover' => 20_000_000,
            'public_liability_expires_on' => now()->addMonths(8),
            'workcover_policy_number' => fake()->numerify('########'),
            'workcover_expires_on' => now()->addMonths(5),
            'documents_checked_on' => now()->subWeek(),
        ]);
    }

    /**
     * Indicate that the vendor takes all kinds of jobs.
     */
    public function general(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => VendorType::General,
            'categories' => null,
        ]);
    }

    /**
     * Indicate that the vendor only takes the given kinds of jobs.
     */
    public function specialist(JobCategory ...$categories): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => VendorType::Specialist,
            'categories' => $categories,
        ]);
    }
}
