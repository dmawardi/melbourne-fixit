<?php

namespace Database\Factories;

use App\Enums\FileKind;
use App\Enums\Visibility;
use App\Models\JobRequest;
use App\Models\JobRequestFile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<JobRequestFile>
 */
class JobRequestFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_request_id' => JobRequest::factory(),
            'uploaded_by' => null,
            'kind' => FileKind::Photo,
            'visibility' => Visibility::Internal,
            'disk' => config('filesystems.job_photos'),
            'path' => fn (array $attributes) => 'job-requests/'
                .JobRequest::whereKey($attributes['job_request_id'])->value('public_id')
                .'/'.Str::lower((string) Str::ulid()).'.jpg',
            'original_name' => 'IMG_'.fake()->numerify('####').'.jpg',
            'mime_type' => 'image/jpeg',
            'size' => fake()->numberBetween(200_000, 4_000_000),
            'width' => 1600,
            'height' => 1200,
            'caption' => fake()->optional(0.5)->sentence(4),
            'sort_order' => 0,
            'uploaded_at' => now(),
        ];
    }

    /**
     * Indicate that the file is shared with the customer.
     */
    public function shared(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => Visibility::Customer,
        ]);
    }

    /**
     * Indicate that the file is only visible to admins.
     */
    public function internal(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => Visibility::Internal,
        ]);
    }

    /**
     * Indicate that the file is a PDF document of the given kind, such as a quote.
     */
    public function pdf(FileKind $kind = FileKind::Other): static
    {
        return $this->state(fn (array $attributes) => [
            'kind' => $kind,
            'path' => fn (array $attributes) => 'job-requests/'
                .JobRequest::whereKey($attributes['job_request_id'])->value('public_id')
                .'/'.Str::lower((string) Str::ulid()).'.pdf',
            'original_name' => Str::slug($kind->label()).'-'.fake()->numerify('####').'.pdf',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(50_000, 500_000),
            'width' => null,
            'height' => null,
            'caption' => null,
        ]);
    }
}
