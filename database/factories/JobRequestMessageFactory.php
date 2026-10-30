<?php

namespace Database\Factories;

use App\Enums\Visibility;
use App\Models\JobRequest;
use App\Models\JobRequestMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobRequestMessage>
 */
class JobRequestMessageFactory extends Factory
{
    /**
     * Sample updates an admin might post.
     *
     * @var list<string>
     */
    protected const UPDATES = [
        'Called the customer to confirm access details. Side gate code is 4821.',
        'Sent the job out to two vendors for quotes.',
        'Handyman confirmed visit today at 3PM.',
        'Vendor quote received, reviewing before sending to the customer.',
        'Customer approved the quote. Booking the job in.',
        'Materials ordered, expected delivery Thursday.',
        'Work completed. Waiting on photos from the tradesperson.',
    ];

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
            'body' => fake()->randomElement(self::UPDATES),
            'visibility' => Visibility::Internal,
        ];
    }

    /**
     * Indicate that the message is shared with the customer.
     */
    public function shared(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => Visibility::Customer,
        ]);
    }
}
