<?php

namespace Tests\Feature\JobRequests;

use App\Enums\ActivityEvent;
use App\Enums\FileKind;
use App\Enums\Visibility;
use App\Models\JobRequest;
use App\Models\JobRequestFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JobRequestFileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.job_photos' => 's3']);
        Storage::fake('s3');
    }

    public function test_owners_can_upload_photos()
    {
        $user = User::factory()->create();
        $jobRequest = JobRequest::factory()->claimedBy($user)->create();

        $this->actingAs($user)
            ->post(route('job-requests.photos.store', $jobRequest), [
                'photos' => [
                    UploadedFile::fake()->image('tap.jpg', 800, 600),
                    UploadedFile::fake()->image('sink.png', 400, 300),
                ],
            ])
            ->assertRedirect(route('job-requests.show', $jobRequest));

        $files = $jobRequest->files()->get();
        $this->assertCount(2, $files);
        $this->assertSame([1, 2], $files->pluck('sort_order')->all());
        $this->assertSame(800, $files[0]->width);
        $this->assertSame(FileKind::Photo, $files[0]->kind);
        $this->assertSame(Visibility::Customer, $files[0]->visibility);
        $this->assertTrue($files[0]->uploader->is($user));
        $this->assertStringStartsWith("job-requests/{$jobRequest->public_id}/", $files[0]->path);
        Storage::disk('s3')->assertExists($files->pluck('path')->all());

        $this->assertSame(2, $jobRequest->activities()->where('event', ActivityEvent::FileUploaded)->count());
        $this->assertNotNull($jobRequest->fresh()->last_activity_at);
    }

    public function test_non_images_are_rejected()
    {
        $user = User::factory()->create();
        $jobRequest = JobRequest::factory()->claimedBy($user)->create();

        $this->actingAs($user)
            ->post(route('job-requests.photos.store', $jobRequest), [
                'photos' => [UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf')],
            ])
            ->assertSessionHasErrors('photos.0');

        $this->assertDatabaseCount('job_request_files', 0);
    }

    public function test_photo_count_is_limited()
    {
        $user = User::factory()->create();
        $jobRequest = JobRequest::factory()->claimedBy($user)->create();
        JobRequestFile::factory()->shared()->count(JobRequest::MAX_PHOTOS - 1)->for($jobRequest)->create(['uploaded_by' => $user->id]);

        $this->actingAs($user)
            ->post(route('job-requests.photos.store', $jobRequest), [
                'photos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
            ])
            ->assertSessionHasErrors('photos');
    }

    public function test_admin_uploads_do_not_count_towards_the_photo_limit()
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $jobRequest = JobRequest::factory()->claimedBy($user)->create();
        JobRequestFile::factory()->shared()->count(JobRequest::MAX_PHOTOS)->for($jobRequest)->create(['uploaded_by' => $admin->id]);

        $this->actingAs($user)
            ->post(route('job-requests.photos.store', $jobRequest), [
                'photos' => [UploadedFile::fake()->image('a.jpg')],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(JobRequest::MAX_PHOTOS + 1, $jobRequest->files()->count());
    }

    public function test_other_users_cannot_upload_photos()
    {
        $jobRequest = JobRequest::factory()->claimedBy(User::factory()->create())->create();

        $this->actingAs(User::factory()->create())
            ->post(route('job-requests.photos.store', $jobRequest), [
                'photos' => [UploadedFile::fake()->image('tap.jpg')],
            ])
            ->assertForbidden();
    }

    public function test_guests_cannot_upload_photos()
    {
        $jobRequest = JobRequest::factory()->create();

        $this->post(route('job-requests.photos.store', $jobRequest), [
            'photos' => [UploadedFile::fake()->image('tap.jpg')],
        ])->assertRedirect(route('login'));
    }

    public function test_owners_can_update_photo_captions()
    {
        $user = User::factory()->create();
        $jobRequest = JobRequest::factory()->claimedBy($user)->create();
        $file = JobRequestFile::factory()->shared()->for($jobRequest)->create(['uploaded_by' => $user->id]);

        $this->actingAs($user)
            ->patch(route('job-requests.photos.update', [$jobRequest, $file]), ['caption' => 'Under the sink'])
            ->assertRedirect();

        $this->assertSame('Under the sink', $file->fresh()->caption);
        $this->assertSame(ActivityEvent::FileUpdated, $jobRequest->activities()->first()->event);
    }

    public function test_owners_can_delete_their_photos_and_the_file_is_removed()
    {
        $user = User::factory()->create();
        $jobRequest = JobRequest::factory()->claimedBy($user)->create();
        $file = JobRequestFile::factory()->shared()->for($jobRequest)->create(['uploaded_by' => $user->id]);
        Storage::disk('s3')->put($file->path, 'image');

        $this->actingAs($user)
            ->delete(route('job-requests.photos.destroy', [$jobRequest, $file]))
            ->assertRedirect(route('job-requests.show', $jobRequest));

        $this->assertModelMissing($file);
        Storage::disk('s3')->assertMissing($file->path);
        $this->assertSame(ActivityEvent::FileDeleted, $jobRequest->activities()->first()->event);
    }

    public function test_customers_cannot_delete_shared_admin_files()
    {
        $user = User::factory()->create();
        $jobRequest = JobRequest::factory()->claimedBy($user)->create();
        $quote = JobRequestFile::factory()->pdf(FileKind::ClientQuote)->shared()->for($jobRequest)
            ->create(['uploaded_by' => User::factory()->admin()->create()->id]);

        $this->actingAs($user)
            ->delete(route('job-requests.photos.destroy', [$jobRequest, $quote]))
            ->assertForbidden();

        $this->assertModelExists($quote);
    }

    public function test_customer_routes_do_not_reveal_internal_files()
    {
        $user = User::factory()->create();
        $jobRequest = JobRequest::factory()->claimedBy($user)->create();
        $internal = JobRequestFile::factory()->internal()->for($jobRequest)->create(['uploaded_by' => $user->id]);

        $this->actingAs($user)
            ->delete(route('job-requests.photos.destroy', [$jobRequest, $internal]))
            ->assertNotFound();

        $this->actingAs($user)
            ->patch(route('job-requests.photos.update', [$jobRequest, $internal]), ['caption' => 'x'])
            ->assertNotFound();

        $this->assertModelExists($internal);
    }

    public function test_files_cannot_be_accessed_through_another_request()
    {
        $user = User::factory()->create();
        $ownRequest = JobRequest::factory()->claimedBy($user)->create();
        $otherFile = JobRequestFile::factory()->shared()->for(JobRequest::factory()->claimedBy(User::factory()->create()))->create();

        $this->actingAs($user)
            ->delete(route('job-requests.photos.destroy', [$ownRequest, $otherFile]))
            ->assertNotFound();

        $this->assertModelExists($otherFile);
    }

    public function test_force_deleting_a_request_removes_its_stored_files()
    {
        $jobRequest = JobRequest::factory()->create();
        $file = JobRequestFile::factory()->for($jobRequest)->create();
        Storage::disk('s3')->put($file->path, 'image');

        $jobRequest->forceDelete();

        Storage::disk('s3')->assertMissing($file->path);
    }
}
