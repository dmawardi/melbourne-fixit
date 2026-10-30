<?php

namespace Database\Seeders;

use App\Enums\ActivityEvent;
use App\Enums\FileKind;
use App\Enums\JobCategory;
use App\Enums\JobRequestStatus;
use App\Enums\VendorType;
use App\Enums\Visibility;
use App\Models\JobRequest;
use App\Models\JobRequestFile;
use App\Models\JobRequestMessage;
use App\Models\User;
use App\Models\Vendor;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

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
     * The order statuses progress through for seeded requests.
     *
     * @var list<JobRequestStatus>
     */
    protected const PROGRESSION = [
        JobRequestStatus::Submitted,
        JobRequestStatus::Reviewing,
        JobRequestStatus::Quoted,
        JobRequestStatus::Scheduled,
        JobRequestStatus::Completed,
    ];

    /**
     * Whether placeholder files could be written to the photos disk.
     */
    protected bool $canWriteFiles = true;

    /**
     * Seed job requests for local development.
     */
    public function run(): void
    {
        $customer = User::firstWhere('email', 'test@example.com')
            ?? User::factory()->create(['name' => 'Test User', 'email' => 'test@example.com']);
        $admin = User::firstWhere('email', 'admin@example.com')
            ?? User::factory()->admin()->create(['name' => 'Admin User', 'email' => 'admin@example.com']);

        if (Vendor::query()->doesntExist()) {
            $this->call(VendorSeeder::class);
        }

        // Captured once, since the clock is moved around while seeding.
        $now = now();

        try {
            // Requests the test user owns, at different stages, so the admin index shows varied activity.
            foreach ([
                [JobRequestStatus::Submitted, 2],
                [JobRequestStatus::Reviewing, 6],
                [JobRequestStatus::Quoted, 10],
                [JobRequestStatus::Completed, 21],
            ] as [$status, $daysAgo]) {
                $this->seedCustomerRequest($customer, $admin, $status, $now->subDays($daysAgo));
            }

            // Submitted as a guest with the test user's email: claimed automatically on their next login.
            $this->at($now->subDay(), function () use ($customer): void {
                JobRequest::factory()
                    ->create(['contact_name' => $customer->name, 'contact_email' => $customer->email])
                    ->recordActivity(ActivityEvent::Created);
            });

            // Guest requests from people who haven't created an account yet.
            foreach (range(1, 14) as $i) {
                $this->at($now->subDays(fake()->numberBetween(0, 14))->subMinutes(fake()->numberBetween(0, 600)), function (): void {
                    JobRequest::factory()->create()->recordActivity(ActivityEvent::Created);
                });
            }

            // Other customers' requests, so the admin list shows more than one customer at each stage.
            foreach ([
                [JobRequestStatus::Reviewing, 4],
                [JobRequestStatus::Quoted, 8],
                [JobRequestStatus::Scheduled, 12],
                [JobRequestStatus::Scheduled, 16],
                [JobRequestStatus::Completed, 30],
            ] as [$status, $daysAgo]) {
                $this->seedCustomerRequest(User::factory()->create(), $admin, $status, $now->subDays($daysAgo));
            }

            $this->seedVendorScenarios($admin, $now);
        } finally {
            Date::setTestNow();
        }
    }

    /**
     * Seed a customer-owned request and move it through the workflow up to the given status.
     */
    protected function seedCustomerRequest(User $customer, User $admin, JobRequestStatus $finalStatus, CarbonImmutable $start): void
    {
        $jobRequest = $this->at($start, function () use ($customer): JobRequest {
            $jobRequest = JobRequest::factory()->claimedBy($customer)->create();
            $jobRequest->recordActivity(ActivityEvent::Created, by: $customer);

            return $jobRequest;
        });

        $this->at($start->addMinutes(10), function () use ($jobRequest, $customer): void {
            foreach (range(1, fake()->numberBetween(1, 3)) as $i) {
                $this->storePhoto($jobRequest, $customer, Visibility::Customer);
            }
        });

        $finalIndex = array_search($finalStatus, self::PROGRESSION, true);

        if ($finalIndex === false) {
            throw new InvalidArgumentException("Can't seed a request with the [{$finalStatus->value}] status.");
        }

        $steps = array_slice(self::PROGRESSION, 1, $finalIndex);
        $time = $start;

        foreach ($steps as $status) {
            $time = $time->addHours(fake()->numberBetween(8, 40));

            $this->at($time, function () use ($jobRequest, $admin, $status): void {
                match ($status) {
                    JobRequestStatus::Reviewing => $this->reviewing($jobRequest, $admin),
                    JobRequestStatus::Quoted => $this->quoted($jobRequest, $admin),
                    JobRequestStatus::Scheduled => $this->scheduled($jobRequest, $admin),
                    JobRequestStatus::Completed => $this->completed($jobRequest, $admin),
                    default => null,
                };
            });
        }
    }

    /**
     * Seed requests showing each way a vendor can end up on, or come off, a job.
     */
    protected function seedVendorScenarios(User $admin, CarbonImmutable $now): void
    {
        // A specialist assigned outside their job types, so the warning shows.
        $this->seedGuestRequest(JobCategory::Roofing, $now->subDays(5), function (JobRequest $jobRequest) use ($admin): void {
            $jobRequest->updateWithActivity(['status' => JobRequestStatus::Reviewing], $admin);
            $jobRequest->assignVendor($this->specialistFor(JobCategory::Plumbing), $admin);
            $this->postMessage($jobRequest, $admin, 'Our usual roofer is booked out, so the plumber will take a look at the downpipe.', Visibility::Internal);
        });

        // A vendor replaced by another.
        $this->seedGuestRequest(JobCategory::Painting, $now->subDays(9), function (JobRequest $jobRequest) use ($admin): void {
            $jobRequest->updateWithActivity(['status' => JobRequestStatus::Reviewing], $admin);
            $jobRequest->assignVendor($this->specialistFor(JobCategory::Painting), $admin);
            $this->postMessage($jobRequest, $admin, "Painter can't start for six weeks, so moving this to another vendor.", Visibility::Internal);
            $jobRequest->assignVendor($this->generalVendor(), $admin);
        });

        // A vendor that pulled out, leaving the job unassigned.
        $this->seedGuestRequest(JobCategory::Fencing, $now->subDays(7), function (JobRequest $jobRequest) use ($admin): void {
            $jobRequest->updateWithActivity(['status' => JobRequestStatus::Reviewing], $admin);
            $jobRequest->assignVendor($this->specialistFor(JobCategory::Fencing), $admin);
            $jobRequest->removeVendor($admin);
            $this->postMessage($jobRequest, $admin, 'Vendor pulled out, so this needs a new vendor.', Visibility::Internal);
        });

        // A job whose category changed after the vendor was assigned, so it no longer matches.
        $this->seedGuestRequest(JobCategory::Plumbing, $now->subDays(6), function (JobRequest $jobRequest) use ($admin): void {
            $jobRequest->assignVendor($this->specialistFor(JobCategory::Plumbing), $admin);
            $jobRequest->updateWithActivity(['status' => JobRequestStatus::Reviewing, 'category' => JobCategory::Tiling], $admin);
        });

        // A vendor archived while still on a job.
        $this->seedGuestRequest(JobCategory::Electrical, $now->subDays(20), function (JobRequest $jobRequest) use ($admin): void {
            $vendor = Vendor::factory()->specialist(JobCategory::Electrical)->create(['name' => 'Sparks & Sons Electrical']);
            $jobRequest->assignVendor($vendor, $admin);
            $jobRequest->updateWithActivity(['status' => JobRequestStatus::Completed], $admin);
            $vendor->delete();
        });

        // A cancelled job, with its vendor taken off.
        $this->seedGuestRequest(JobCategory::Landscaping, $now->subDays(11), function (JobRequest $jobRequest) use ($admin): void {
            $jobRequest->assignVendor($this->specialistFor(JobCategory::Landscaping), $admin);
            $jobRequest->removeVendor($admin);
            $jobRequest->updateWithActivity(['status' => JobRequestStatus::Cancelled], $admin);
            $this->postMessage($jobRequest, $admin, 'Customer decided not to go ahead.', Visibility::Internal);
        });

        // A booked job with no street address yet.
        $this->seedGuestRequest(JobCategory::GeneralHandyman, $now->subDays(3), function (JobRequest $jobRequest) use ($admin): void {
            $jobRequest->assignVendor($this->generalVendor(), $admin);
            $jobRequest->updateWithActivity(['status' => JobRequestStatus::Scheduled], $admin);
        });
    }

    /**
     * Seed a guest request, then run the callback a day after it was submitted.
     *
     * @param  callable(JobRequest): void  $callback
     */
    protected function seedGuestRequest(JobCategory $category, CarbonImmutable $start, callable $callback): void
    {
        $jobRequest = $this->at($start, function () use ($category): JobRequest {
            $jobRequest = JobRequest::factory()->forCategory($category)->create();
            $jobRequest->recordActivity(ActivityEvent::Created);

            return $jobRequest;
        });

        $this->at($start->addDay(), fn () => $callback($jobRequest));
    }

    /**
     * Get a specialist that takes the given job type, adding one if there isn't one yet.
     */
    protected function specialistFor(JobCategory $category): Vendor
    {
        return Vendor::query()->where('type', VendorType::Specialist)->get()
            ->first(fn (Vendor $vendor): bool => $vendor->canHandle($category))
            ?? Vendor::factory()->specialist($category)->create();
    }

    protected function generalVendor(): Vendor
    {
        return Vendor::query()->where('type', VendorType::General)->first()
            ?? Vendor::factory()->general()->create();
    }

    protected function reviewing(JobRequest $jobRequest, User $admin): void
    {
        $jobRequest->updateWithActivity([
            'status' => JobRequestStatus::Reviewing,
            'admin_notes' => 'Customer prefers contact by phone after 4pm.',
        ], $admin);

        $this->postMessage($jobRequest, $admin, 'Sent the job out to two vendors for quotes.', Visibility::Internal);
        $this->storeDocument($jobRequest, $admin, FileKind::VendorQuote, Visibility::Internal);
        $this->storeDocument($jobRequest, $admin, FileKind::VendorQuote, Visibility::Internal);
    }

    protected function quoted(JobRequest $jobRequest, User $admin): void
    {
        $vendor = Vendor::all()->filter(fn (Vendor $vendor): bool => $vendor->canHandle($jobRequest->category))->random();
        $jobRequest->assignVendor($vendor, $admin);

        $this->storeDocument($jobRequest, $admin, FileKind::ClientQuote, Visibility::Customer);
        $jobRequest->updateWithActivity(['status' => JobRequestStatus::Quoted], $admin);
        $this->postMessage($jobRequest, $admin, "Your quote is ready. Let us know if you'd like to go ahead.", Visibility::Customer);
    }

    protected function scheduled(JobRequest $jobRequest, User $admin): void
    {
        $jobRequest->updateWithActivity(['status' => JobRequestStatus::Scheduled], $admin);
        $this->postMessage($jobRequest, $admin, 'Handyman confirmed visit today at 3PM.', Visibility::Internal);
        $this->postMessage($jobRequest, $admin, 'Your job is booked in for this afternoon.', Visibility::Customer);
    }

    protected function completed(JobRequest $jobRequest, User $admin): void
    {
        $this->storePhoto($jobRequest, $admin, Visibility::Customer);
        $jobRequest->updateWithActivity(['status' => JobRequestStatus::Completed], $admin);
        $this->postMessage($jobRequest, $admin, 'Work completed and signed off by the customer.', Visibility::Internal);
    }

    /**
     * Run the callback with the clock set to the given time.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    protected function at(CarbonImmutable $time, callable $callback): mixed
    {
        Date::setTestNow($time);

        return $callback();
    }

    protected function postMessage(JobRequest $jobRequest, User $admin, string $body, Visibility $visibility): void
    {
        $message = new JobRequestMessage(['body' => $body, 'visibility' => $visibility]);
        $message->user_id = $admin->id;
        $jobRequest->messages()->save($message);

        $jobRequest->recordActivity(ActivityEvent::MessagePosted, [
            'message_id' => $message->id,
            'excerpt' => $message->excerpt(),
            'visibility' => $visibility->value,
        ], $admin);
    }

    protected function storePhoto(JobRequest $jobRequest, User $uploader, Visibility $visibility): void
    {
        $colour = self::COLOURS[($jobRequest->id + $jobRequest->files()->count()) % count(self::COLOURS)];

        $this->storeFile(
            $jobRequest,
            $uploader,
            JobRequestFile::factory()->state([
                'visibility' => $visibility,
                'caption' => null,
                'width' => self::PHOTO_WIDTH,
                'height' => self::PHOTO_HEIGHT,
            ]),
            $this->placeholderImage($colour),
        );
    }

    protected function storeDocument(JobRequest $jobRequest, User $uploader, FileKind $kind, Visibility $visibility): void
    {
        $this->storeFile(
            $jobRequest,
            $uploader,
            JobRequestFile::factory()->pdf($kind)->state(['visibility' => $visibility]),
            $this->placeholderPdf($kind->label().' for '.$jobRequest->title),
        );
    }

    /**
     * Write placeholder contents to the photos disk and record the file.
     *
     * @param  Factory<JobRequestFile>  $factory
     */
    protected function storeFile(JobRequest $jobRequest, User $uploader, Factory $factory, string $contents): void
    {
        if (! $this->canWriteFiles) {
            return;
        }

        $disk = config('filesystems.job_photos');

        /** @var JobRequestFile $file */
        $file = $factory->make([
            'job_request_id' => $jobRequest->id,
            'disk' => $disk,
            'sort_order' => (int) $jobRequest->files()->max('sort_order') + 1,
        ]);

        if (! Storage::disk($disk)->put($file->path, $contents, 'private')) {
            $this->canWriteFiles = false;
            $this->command?->warn("Couldn't write placeholder files to the [{$disk}] disk; skipping files.");

            return;
        }

        $file->size = strlen($contents);
        $file->uploaded_by = $uploader->id;
        $file->save();

        $jobRequest->recordActivity(ActivityEvent::FileUploaded, $file->activityProperties(), $uploader);
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

    /**
     * Generate a minimal one-page PDF containing a line of text, to stand in for a quote.
     */
    protected function placeholderPdf(string $text): string
    {
        $text = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        $stream = "BT /F1 18 Tf 72 720 Td ({$text}) Tj ET";

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= 'xref'."\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf.'trailer'."\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    }
}
