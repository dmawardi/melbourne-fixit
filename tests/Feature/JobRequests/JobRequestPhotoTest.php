<?php

namespace Tests\Feature\JobRequests;

use App\Models\JobRequest;
use App\Models\JobRequestPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JobRequestPhotoTest extends TestCase
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

        $photos = $jobRequest->photos()->get();
        $this->assertCount(2, $photos);
        $this->assertSame([1, 2], $photos->pluck('sort_order')->all());
        $this->assertSame(800, $photos[0]->width);
        $this->assertTrue($photos[0]->uploader->is($user));
        $this->assertStringStartsWith("job-requests/{$jobRequest->public_id}/", $photos[0]->path);
        Storage::disk('s3')->assertExists($photos->pluck('path')->all());
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

        $this->assertDatabaseCount('job_request_photos', 0);
    }

    public function test_photo_count_is_limited()
    {
        $user = User::factory()->create();
        $jobRequest = JobRequest::factory()->claimedBy($user)->create();
        JobRequestPhoto::factory()->count(JobRequest::MAX_PHOTOS - 1)->for($jobRequest)->create();

        $this->actingAs($user)
            ->post(route('job-requests.photos.store', $jobRequest), [
                'photos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
            ])
            ->assertSessionHasErrors('photos');
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
        $photo = JobRequestPhoto::factory()->for($jobRequest)->create();

        $this->actingAs($user)
            ->patch(route('job-requests.photos.update', [$jobRequest, $photo]), ['caption' => 'Under the sink'])
            ->assertRedirect();

        $this->assertSame('Under the sink', $photo->fresh()->caption);
    }

    public function test_owners_can_delete_photos_and_the_file_is_removed()
    {
        $user = User::factory()->create();
        $jobRequest = JobRequest::factory()->claimedBy($user)->create();
        $photo = JobRequestPhoto::factory()->for($jobRequest)->create();
        Storage::disk('s3')->put($photo->path, 'image');

        $this->actingAs($user)
            ->delete(route('job-requests.photos.destroy', [$jobRequest, $photo]))
            ->assertRedirect(route('job-requests.show', $jobRequest));

        $this->assertModelMissing($photo);
        Storage::disk('s3')->assertMissing($photo->path);
    }

    public function test_photos_cannot_be_accessed_through_another_request()
    {
        $user = User::factory()->create();
        $ownRequest = JobRequest::factory()->claimedBy($user)->create();
        $otherPhoto = JobRequestPhoto::factory()->for(JobRequest::factory()->claimedBy(User::factory()->create()))->create();

        $this->actingAs($user)
            ->delete(route('job-requests.photos.destroy', [$ownRequest, $otherPhoto]))
            ->assertNotFound();

        $this->assertModelExists($otherPhoto);
    }

    public function test_force_deleting_a_request_removes_its_photo_files()
    {
        $jobRequest = JobRequest::factory()->create();
        $photo = JobRequestPhoto::factory()->for($jobRequest)->create();
        Storage::disk('s3')->put($photo->path, 'image');

        $jobRequest->forceDelete();

        Storage::disk('s3')->assertMissing($photo->path);
    }
}
