<?php

namespace Database\Seeders;

use App\Enums\JobRequestStatus;
use App\Models\JobRequest;
use App\Models\JobRequestPhoto;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class JobRequestSeeder extends Seeder
{
    /**
     * Placeholder photo dimensions, in pixels.
     */
    protected const PHOTO_WIDTH = 1600;

    protected const PHOTO_HEIGHT = 1200;

    /**
     * Placeholder photo colours, so seeded photos are visually distinguishable.
     *
     * @var list<array{0: int<0, 255>, 1: int<0, 255>, 2: int<0, 255>}>
     */
    protected const COLOURS = [
        [71, 85, 105],
        [180, 83, 9],
        [21, 128, 61],
        [29, 78, 216],
        [190, 18, 60],
    ];

    /**
     * Seed job requests for local development.
     */
    public function run(): void
    {
        $customer = User::firstWhere('email', 'test@example.com')
            ?? User::factory()->create(['name' => 'Test User', 'email' => 'test@example.com']);

        // Requests the test user already owns, across a range of statuses.
        foreach ([
            JobRequestStatus::Submitted,
            JobRequestStatus::Reviewing,
            JobRequestStatus::Quoted,
            JobRequestStatus::Completed,
        ] as $status) {
            $jobRequest = JobRequest::factory()->claimedBy($customer)->status($status)->create();

            $this->attachPhotos($jobRequest, $customer, fake()->numberBetween(1, 3));
        }

        // Submitted as a guest with the test user's email: claimed automatically on their next login.
        JobRequest::factory()->create([
            'contact_name' => $customer->name,
            'contact_email' => $customer->email,
        ]);

        // Guest requests from people who haven't created an account yet.
        JobRequest::factory()->count(8)->create();
    }

    /**
     * Attach generated placeholder photos to a job request.
     */
    protected function attachPhotos(JobRequest $jobRequest, User $uploader, int $count): void
    {
        $disk = config('filesystems.job_photos');

        for ($i = 0; $i < $count; $i++) {
            $photo = JobRequestPhoto::factory()->make([
                'job_request_id' => $jobRequest->id,
                'disk' => $disk,
                'caption' => null,
                'sort_order' => $i + 1,
                'width' => self::PHOTO_WIDTH,
                'height' => self::PHOTO_HEIGHT,
            ]);

            $contents = $this->placeholderImage(self::COLOURS[($jobRequest->id + $i) % count(self::COLOURS)]);

            if (! Storage::disk($disk)->put($photo->path, $contents, 'private')) {
                $this->command?->warn("Couldn't write placeholder photo to the [{$disk}] disk; skipping photos.");

                return;
            }

            $photo->size = strlen($contents);
            $photo->uploaded_by = $uploader->id;
            $photo->save();
        }
    }

    /**
     * Generate a solid-colour JPEG to stand in for a real photo.
     *
     * @param  array{0: int<0, 255>, 1: int<0, 255>, 2: int<0, 255>}  $rgb
     */
    protected function placeholderImage(array $rgb): string
    {
        $image = imagecreatetruecolor(self::PHOTO_WIDTH, self::PHOTO_HEIGHT);
        $colour = imagecolorallocate($image, ...$rgb);

        if ($colour !== false) {
            imagefill($image, 0, 0, $colour);
        }

        ob_start();
        imagejpeg($image, null, 80);

        return (string) ob_get_clean();
    }
}
