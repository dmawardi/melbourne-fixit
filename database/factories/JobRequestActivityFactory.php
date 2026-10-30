<?php

namespace Database\Factories;

use App\Enums\ActivityEvent;
use App\Models\JobRequest;
use App\Models\JobRequestActivity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobRequestActivity>
 */
class JobRequestActivityFactory extends Factory
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
            'user_id' => null,
            'event' => ActivityEvent::Created,
            'properties' => null,
        ];
    }
}
