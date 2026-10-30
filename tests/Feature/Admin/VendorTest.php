<?php

namespace Tests\Feature\Admin;

use App\Enums\JobCategory;
use App\Enums\VendorType;
use App\Models\JobRequest;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admins_can_list_vendors_without_archived_ones()
    {
        $vendor = Vendor::factory()->specialist(JobCategory::Plumbing)->create(['name' => 'Northside Plumbing']);
        JobRequest::factory()->create()->assignVendor($vendor);
        Vendor::factory()->create()->delete();

        $this->actingAs($this->admin)
            ->get(route('admin.vendors.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/vendors/index')
                ->has('vendors.data', 1)
                ->where('vendors.data.0.name', 'Northside Plumbing')
                ->where('vendors.data.0.categories.0.value', 'plumbing')
                ->where('vendors.data.0.job_requests_count', 1)
            );
    }

    public function test_the_vendor_forms_render()
    {
        $vendor = Vendor::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('admin.vendors.create'))
            ->assertInertia(fn ($page) => $page->component('admin/vendors/create')->has('options.types', 2));

        $this->actingAs($this->admin)
            ->get(route('admin.vendors.edit', $vendor))
            ->assertInertia(fn ($page) => $page->component('admin/vendors/edit')->where('vendor.id', $vendor->id));
    }

    public function test_admins_can_add_a_general_vendor()
    {
        $this->actingAs($this->admin)
            ->post(route('admin.vendors.store'), ['name' => 'Bayside Building', 'type' => 'general'])
            ->assertRedirect(route('admin.vendors.index'));

        $vendor = Vendor::sole();
        $this->assertSame(VendorType::General, $vendor->type);
        $this->assertNull($vendor->categories);
        $this->assertTrue($vendor->canHandle(JobCategory::Roofing));
    }

    public function test_admins_can_add_a_specialist_vendor()
    {
        $this->actingAs($this->admin)
            ->post(route('admin.vendors.store'), [
                'name' => 'Fresh Coat',
                'type' => 'specialist',
                'categories' => ['painting', 'plastering'],
            ])
            ->assertRedirect(route('admin.vendors.index'));

        $vendor = Vendor::sole();
        $this->assertTrue($vendor->canHandle(JobCategory::Painting));
        $this->assertFalse($vendor->canHandle(JobCategory::Roofing));
    }

    public function test_specialists_need_valid_categories()
    {
        $this->actingAs($this->admin)
            ->post(route('admin.vendors.store'), ['name' => 'Fresh Coat', 'type' => 'specialist'])
            ->assertSessionHasErrors('categories');

        $this->actingAs($this->admin)
            ->post(route('admin.vendors.store'), ['name' => 'Fresh Coat', 'type' => 'specialist', 'categories' => ['not-a-category']])
            ->assertSessionHasErrors('categories.0');

        $this->assertSame(0, Vendor::count());
    }

    public function test_vendors_require_a_name_and_type()
    {
        $this->actingAs($this->admin)
            ->post(route('admin.vendors.store'), [])
            ->assertSessionHasErrors(['name', 'type']);
    }

    public function test_changing_a_specialist_to_general_clears_its_categories()
    {
        $vendor = Vendor::factory()->specialist(JobCategory::Plumbing)->create();

        $this->actingAs($this->admin)
            ->put(route('admin.vendors.update', $vendor), [
                'name' => $vendor->name,
                'type' => 'general',
                'categories' => ['plumbing'],
            ])
            ->assertRedirect(route('admin.vendors.index'));

        $vendor->refresh();
        $this->assertSame(VendorType::General, $vendor->type);
        $this->assertNull($vendor->categories);
    }

    public function test_admins_can_archive_vendors()
    {
        $vendor = Vendor::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.vendors.destroy', $vendor))
            ->assertRedirect(route('admin.vendors.index'));

        $this->assertSoftDeleted($vendor);
    }

    public function test_archived_vendors_cannot_be_edited()
    {
        $vendor = Vendor::factory()->create();
        $vendor->delete();

        $this->actingAs($this->admin)
            ->get(route('admin.vendors.edit', $vendor))
            ->assertNotFound();
    }
}
