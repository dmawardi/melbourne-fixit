<?php

namespace Database\Factories;

use App\Models\JobRequest;
use App\Models\JobRequestPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<JobRequestPhoto>
 */
class JobRequestPhotoFactory extends Factory
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
}
