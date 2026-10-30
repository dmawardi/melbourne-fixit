<?php

namespace Database\Factories;

use App\Enums\BudgetRange;
use App\Enums\JobCategory;
use App\Enums\JobRequestStatus;
use App\Enums\JobUrgency;
use App\Enums\PropertyType;
use App\Models\JobRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobRequest>
 */
class JobRequestFactory extends Factory
{
    /**
     * A sample of Melbourne suburbs and their postcodes.
     *
     * @var array<string, string>
     */
    protected const SUBURBS = [
        'Fitzroy' => '3065',
        'Brunswick' => '3056',
        'Richmond' => '3121',
        'St Kilda' => '3182',
        'Footscray' => '3011',
        'Coburg' => '3058',
        'Northcote' => '3070',
        'Hawthorn' => '3122',
        'Box Hill' => '3128',
        'Glen Waverley' => '3150',
        'Williamstown' => '3016',
        'Preston' => '3072',
        'Camberwell' => '3124',
        'Doncaster' => '3108',
        'Frankston' => '3199',
    ];

    /**
     * Sample job titles and descriptions keyed by category.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    protected const JOBS = [
        'plumbing' => ['Leaking kitchen tap', 'The kitchen mixer tap has been dripping constantly for a week and the base is starting to leak under the sink.'],
        'electrical' => ['Install outdoor power point', 'Need a weatherproof double power point installed on the back deck near the barbecue area.'],
        'carpentry' => ['Repair sagging back deck', 'A section of the timber deck is sagging and a couple of boards have rotted through. Roughly 4m x 3m.'],
        'painting' => ['Repaint lounge and hallway', 'Looking to repaint walls and ceilings in the lounge room and hallway. Some minor patching required.'],
        'plastering' => ['Patch water-damaged ceiling', 'Ceiling in the laundry has a water stain and some bubbling after a roof leak, which has since been fixed.'],
        'tiling' => ['Replace cracked bathroom tiles', 'Around six floor tiles in the main bathroom are cracked. We have some spare tiles from the original job.'],
        'roofing' => ['Clean and repair gutters', 'Gutters overflow during heavy rain. Need cleaning and possibly a downpipe replaced at the rear.'],
        'flooring' => ['Sand and polish floorboards', 'Original baltic pine floorboards in three bedrooms need sanding and re-coating. About 40 square metres.'],
        'fencing' => ['Replace side fence', 'Paling side fence is leaning and has several broken palings. Approximately 15m long, shared with neighbour.'],
        'landscaping' => ['Backyard tidy up and new turf', 'Overgrown backyard needs clearing, levelling and new turf laid. Around 60 square metres.'],
        'kitchen_renovation' => ['Full kitchen renovation', 'Looking to fully renovate a 1990s kitchen including new cabinetry, benchtops, splashback and appliances.'],
        'bathroom_renovation' => ['Ensuite renovation', 'Small ensuite needs a complete renovation: new shower, vanity, toilet, tiling and waterproofing.'],
        'general_handyman' => ['Hang shelves and fix doors', 'A few small jobs: hang floating shelves, adjust two sticking doors and replace a broken cupboard hinge.'],
        'other' => ['Install security screen door', 'Need a security screen door supplied and fitted to the front entrance.'],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $category = fake()->randomElement(JobCategory::cases());
        [$title, $description] = self::JOBS[$category->value];
        $suburb = fake()->randomElement(array_keys(self::SUBURBS));

        return [
            'user_id' => null,
            'claimed_at' => null,
            'contact_name' => fake()->name(),
            'contact_email' => fake()->unique()->safeEmail(),
            'contact_phone' => '04'.fake()->numerify('## ### ###'),
            'suburb' => $suburb,
            'postcode' => self::SUBURBS[$suburb],
            'address_line' => null,
            'category' => $category,
            'title' => $title,
            'description' => $description,
            'urgency' => fake()->randomElement(JobUrgency::cases()),
            'budget_range' => fake()->optional(0.8)->randomElement(BudgetRange::cases()),
            'property_type' => fake()->optional(0.9)->randomElement(PropertyType::cases()),
            'status' => JobRequestStatus::Submitted,
            'submitted_ip' => fake()->ipv4(),
            'submitted_user_agent' => fake()->userAgent(),
        ];
    }

    /**
     * Indicate that the request is for the given kind of job, with a matching title and description.
     */
    public function forCategory(JobCategory $category): static
    {
        [$title, $description] = self::JOBS[$category->value];

        return $this->state(fn (array $attributes) => [
            'category' => $category,
            'title' => $title,
            'description' => $description,
        ]);
    }

    /**
     * Indicate that the request has been claimed by the given user.
     */
    public function claimedBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
            'claimed_at' => now(),
            'contact_name' => $user->name,
            'contact_email' => $user->email,
            'address_line' => fake()->streetAddress(),
        ]);
    }

    /**
     * Indicate that the request has the given status.
     */
    public function status(JobRequestStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}
