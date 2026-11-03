<?php

namespace Tests\Feature\Admin;

use App\Enums\JobCategory;
use App\Enums\VendorDocument;
use App\Enums\VendorDocumentStatus;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VendorFileTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.vendor_documents' => 's3']);
        Storage::fake('s3');
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admins_can_upload_a_copy_of_a_vendor_document()
    {
        $vendor = Vendor::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.vendors.files.store', $vendor), [
                'files' => [UploadedFile::fake()->create('certificate-of-currency.pdf', 300, 'application/pdf')],
                'document' => 'public_liability',
            ])
            ->assertRedirect(route('admin.vendors.edit', $vendor));

        $file = $vendor->files()->sole();
        $this->assertSame(VendorDocument::PublicLiability, $file->document);
        $this->assertSame('certificate-of-currency.pdf', $file->original_name);
        $this->assertStringStartsWith("vendors/{$vendor->id}/", $file->path);
        $this->assertTrue($file->uploader->is($this->admin));
        Storage::disk('s3')->assertExists($file->path);
    }

    public function test_files_without_a_document_are_stored_as_other()
    {
        $vendor = Vendor::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.vendors.files.store', $vendor), [
                'files' => [UploadedFile::fake()->image('licence-card.jpg'), UploadedFile::fake()->image('licence-back.jpg')],
                'document' => '',
            ])
            ->assertRedirect();

        $this->assertSame(2, $vendor->files()->whereNull('document')->count());
    }

    public function test_only_pdfs_and_images_are_accepted()
    {
        $vendor = Vendor::factory()->create();

        foreach (['script.exe' => 'application/x-msdownload', 'letter.docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'] as $name => $mime) {
            $this->actingAs($this->admin)
                ->post(route('admin.vendors.files.store', $vendor), [
                    'files' => [UploadedFile::fake()->create($name, 10, $mime)],
                ])
                ->assertSessionHasErrors('files.0');
        }

        $this->actingAs($this->admin)
            ->post(route('admin.vendors.files.store', $vendor), [
                'files' => [UploadedFile::fake()->create('huge.pdf', 20481, 'application/pdf')],
            ])
            ->assertSessionHasErrors('files.0');

        $this->assertSame(0, $vendor->files()->count());
    }

    public function test_the_document_must_be_a_known_type()
    {
        $vendor = Vendor::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.vendors.files.store', $vendor), [
                'files' => [UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')],
                'document' => 'passport',
            ])
            ->assertSessionHasErrors('document');
    }

    public function test_admins_can_delete_a_file()
    {
        $file = VendorFile::factory()->create();
        Storage::disk('s3')->put($file->path, 'pdf');

        $this->actingAs($this->admin)
            ->delete(route('admin.vendors.files.destroy', [$file->vendor, $file]))
            ->assertRedirect(route('admin.vendors.edit', $file->vendor));

        $this->assertModelMissing($file);
        Storage::disk('s3')->assertMissing($file->path);
    }

    public function test_files_from_another_vendor_are_not_found()
    {
        $vendor = Vendor::factory()->create();
        $otherFile = VendorFile::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.vendors.files.destroy', [$vendor, $otherFile]))
            ->assertNotFound();

        $this->assertModelExists($otherFile);
    }

    public function test_the_edit_page_lists_files_and_the_documents_to_collect()
    {
        $vendor = Vendor::factory()->specialist(JobCategory::Painting)->create();
        $file = VendorFile::factory()->for($vendor)->create(['uploaded_by' => $this->admin->id]);

        $this->actingAs($this->admin)
            ->get(route('admin.vendors.edit', $vendor))
            ->assertInertia(fn ($page) => $page
                ->component('admin/vendors/edit')
                ->has('files', 1)
                ->where('files.0.id', $file->id)
                ->where('files.0.document', 'public_liability')
                ->where('files.0.uploader', $this->admin->name)
                ->where('files.0.url', fn (string $url) => str_contains($url, $file->path))
                ->missing('files.0.path')
                ->where('fileDocuments', fn ($documents) => collect($documents)->pluck('value')->all() === [
                    'business', 'public_liability', 'workcover', 'builder_registration',
                ]));
    }

    public function test_missing_copies_do_not_affect_the_document_status()
    {
        $vendor = Vendor::factory()->withDocuments()->create(['documents_checked_on' => now()]);

        $this->assertSame(0, $vendor->files()->count());
        $this->assertSame(VendorDocumentStatus::UpToDate, $vendor->documentStatus());
    }
}
