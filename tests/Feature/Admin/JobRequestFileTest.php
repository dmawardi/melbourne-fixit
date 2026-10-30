<?php

namespace Tests\Feature\Admin;

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

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.job_photos' => 's3']);
        Storage::fake('s3');
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admins_can_upload_internal_vendor_quotes()
    {
        $jobRequest = JobRequest::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.job-requests.files.store', $jobRequest), [
                'files' => [UploadedFile::fake()->create('vendor-quote.pdf', 300, 'application/pdf')],
                'kind' => 'vendor_quote',
                'visibility' => 'internal',
            ])
            ->assertRedirect(route('admin.job-requests.show', $jobRequest));

        $file = $jobRequest->files()->sole();
        $this->assertSame(FileKind::VendorQuote, $file->kind);
        $this->assertSame(Visibility::Internal, $file->visibility);
        $this->assertSame('vendor-quote.pdf', $file->original_name);
        $this->assertNull($file->width);
        $this->assertTrue($file->uploader->is($this->admin));
        Storage::disk('s3')->assertExists($file->path);

        $activity = $jobRequest->activities()->sole();
        $this->assertSame(ActivityEvent::FileUploaded, $activity->event);
        $this->assertSame('internal', $activity->properties['visibility']);
    }

    public function test_admins_can_upload_shared_client_quotes_and_photos()
    {
        $jobRequest = JobRequest::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.job-requests.files.store', $jobRequest), [
                'files' => [
                    UploadedFile::fake()->create('quote.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
                    UploadedFile::fake()->image('site.jpg', 640, 480),
                ],
                'kind' => 'client_quote',
                'visibility' => 'customer',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $jobRequest->files()->shared()->count());
    }

    public function test_disallowed_file_types_are_rejected()
    {
        $jobRequest = JobRequest::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.job-requests.files.store', $jobRequest), [
                'files' => [UploadedFile::fake()->create('script.exe', 10, 'application/x-msdownload')],
                'kind' => 'other',
                'visibility' => 'internal',
            ])
            ->assertSessionHasErrors('files.0');
    }

    public function test_kind_and_visibility_are_required()
    {
        $jobRequest = JobRequest::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.job-requests.files.store', $jobRequest), [
                'files' => [UploadedFile::fake()->image('a.jpg')],
            ])
            ->assertSessionHasErrors(['kind', 'visibility']);
    }

    public function test_admins_can_share_a_file_and_it_is_logged()
    {
        $jobRequest = JobRequest::factory()->create();
        $file = JobRequestFile::factory()->pdf(FileKind::ClientQuote)->internal()->for($jobRequest)->create();

        $this->actingAs($this->admin)
            ->patch(route('admin.job-requests.files.update', [$jobRequest, $file]), ['visibility' => 'customer'])
            ->assertRedirect();

        $this->assertSame(Visibility::Customer, $file->fresh()->visibility);

        $activity = $jobRequest->activities()->sole();
        $this->assertSame(ActivityEvent::FileUpdated, $activity->event);
        $this->assertSame(['from' => 'internal', 'to' => 'customer'], $activity->properties['changes']['visibility']);
    }

    public function test_admins_can_delete_any_file()
    {
        $customer = User::factory()->create();
        $jobRequest = JobRequest::factory()->claimedBy($customer)->create();
        $file = JobRequestFile::factory()->shared()->for($jobRequest)->create(['uploaded_by' => $customer->id]);
        Storage::disk('s3')->put($file->path, 'image');

        $this->actingAs($this->admin)
            ->delete(route('admin.job-requests.files.destroy', [$jobRequest, $file]))
            ->assertRedirect(route('admin.job-requests.show', $jobRequest));

        $this->assertModelMissing($file);
        Storage::disk('s3')->assertMissing($file->path);
        $this->assertSame(ActivityEvent::FileDeleted, $jobRequest->activities()->sole()->event);
    }

    public function test_files_from_another_request_are_not_found()
    {
        $jobRequest = JobRequest::factory()->create();
        $otherFile = JobRequestFile::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.job-requests.files.destroy', [$jobRequest, $otherFile]))
            ->assertNotFound();
    }
}
