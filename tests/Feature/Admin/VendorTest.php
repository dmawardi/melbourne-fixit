<?php

namespace Tests\Feature\Admin;

use App\Enums\JobCategory;
use App\Enums\VendorDocumentStatus;
use App\Enums\VendorType;
use App\Models\JobRequest;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
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

    public function test_vendors_are_listed_by_document_status_and_can_be_filtered()
    {
        Vendor::factory()->withDocuments()->create(['name' => 'A Up To Date']);
        Vendor::factory()->withDocuments()->create(['name' => 'B Expiring', 'workcover_expires_on' => now()->addDays(10)]);
        Vendor::factory()->withDocuments()->create(['name' => 'C Expired', 'public_liability_expires_on' => now()->subDays(10)]);
        Vendor::factory()->withDocuments()->create(['name' => 'D Archived', 'public_liability_expires_on' => now()->subDays(10)])->delete();

        $this->actingAs($this->admin)
            ->get(route('admin.vendors.index'))
            ->assertInertia(fn ($page) => $page
                ->where('vendors.data.0.name', 'C Expired')
                ->where('vendors.data.0.document_status.value', 'action_needed')
                ->where('vendors.data.1.name', 'B Expiring')
                ->where('vendors.data.2.name', 'A Up To Date')
                ->where('counts', ['all' => 3, 'action_needed' => 1, 'expiring_soon' => 1])
            );

        $this->actingAs($this->admin)
            ->get(route('admin.vendors.index', ['documents' => 'expiring_soon']))
            ->assertInertia(fn ($page) => $page
                ->has('vendors.data', 1)
                ->where('vendors.data.0.name', 'B Expiring')
                ->where('filters.documents', 'expiring_soon')
                ->where('counts.all', 3)
            );
    }

    public function test_the_vendor_list_is_paginated_and_keeps_its_filter()
    {
        Vendor::factory()->count(26)->create();

        $this->actingAs($this->admin)
            ->get(route('admin.vendors.index', ['documents' => 'action_needed', 'page' => 2]))
            ->assertInertia(fn ($page) => $page
                ->has('vendors.data', 1)
                ->where('vendors.meta.total', 26)
                ->where('vendors.links.prev', fn (string $url): bool => str_contains($url, 'documents=action_needed'))
            );
    }

    public function test_expiry_follows_the_melbourne_calendar()
    {
        // 1 May in Melbourne, but still 30 April in UTC.
        Date::setTestNow('2027-04-30 20:00:00');

        $vendor = Vendor::factory()->withDocuments()->create([
            'documents_checked_on' => '2027-04-01',
            'workcover_expires_on' => '2027-12-01',
            'public_liability_expires_on' => '2027-05-01',
        ]);
        $this->assertSame(VendorDocumentStatus::ExpiringSoon, $vendor->documentStatus());

        $vendor->update(['public_liability_expires_on' => '2027-04-30']);
        $this->assertSame(['Public liability insurance expired 30 Apr 2027'], array_column($vendor->documentIssues(), 'message'));
    }

    public function test_documents_are_flagged_from_thirty_days_before_expiry()
    {
        Date::setTestNow('2027-01-01 01:00:00');

        $vendor = Vendor::factory()->withDocuments()->create([
            'documents_checked_on' => '2026-12-01',
            'workcover_expires_on' => '2027-12-01',
            'public_liability_expires_on' => '2027-02-01',
        ]);
        $this->assertSame(VendorDocumentStatus::UpToDate, $vendor->documentStatus());

        $vendor->update(['public_liability_expires_on' => '2027-01-31']);
        $this->assertSame(VendorDocumentStatus::ExpiringSoon, $vendor->documentStatus());
    }

    public function test_only_documents_certain_to_be_required_are_flagged_as_missing()
    {
        $general = Vendor::factory()->general()->create(['documents_checked_on' => now()]);
        $this->assertSame(
            ['ABN missing', 'Public liability insurance missing'],
            array_column($general->documentIssues(), 'message'),
        );

        $electrician = Vendor::factory()->specialist(JobCategory::Electrical)->create(['documents_checked_on' => now()]);
        $this->assertContains('Registered electrical contractor missing', array_column($electrician->documentIssues(), 'message'));

        $renovator = Vendor::factory()->specialist(JobCategory::BathroomRenovation)->create(['documents_checked_on' => now()]);
        $this->assertNotContains('Plumbing registration or licence missing', array_column($renovator->documentIssues(), 'message'));
    }

    public function test_documents_that_no_longer_apply_are_not_flagged()
    {
        $vendor = Vendor::factory()->withDocuments()->specialist(JobCategory::Painting)->create([
            'plumbing_licence_number' => '54321',
            'plumbing_licence_expires_on' => now()->subYear(),
        ]);

        $this->assertSame(VendorDocumentStatus::UpToDate, $vendor->documentStatus());
    }

    public function test_documents_need_checking_at_least_every_twelve_months()
    {
        Date::setTestNow('2027-06-15 01:00:00');

        $vendor = Vendor::factory()->withDocuments()->create(['documents_checked_on' => null]);
        $this->assertSame(['Documents never checked'], array_column($vendor->documentIssues(), 'message'));

        $vendor->update(['documents_checked_on' => '2026-06-15']);
        $this->assertSame([], $vendor->documentIssues());

        $vendor->update(['documents_checked_on' => '2026-06-14']);
        $this->assertSame(VendorDocumentStatus::ActionNeeded, $vendor->documentStatus());
    }

    public function test_documents_can_be_marked_as_checked_today_in_melbourne()
    {
        // 1 May in Melbourne, but still 30 April in UTC.
        Date::setTestNow('2027-04-30 20:00:00');

        $this->actingAs($this->admin)
            ->post(route('admin.vendors.store'), ['name' => 'Test', 'type' => 'general', 'documents_checked_on' => '2027-05-01'])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin)
            ->post(route('admin.vendors.store'), ['name' => 'Test', 'type' => 'general', 'documents_checked_on' => '2027-05-02'])
            ->assertSessionHasErrors('documents_checked_on');
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

    public function test_the_vendor_form_lists_the_documents_to_collect()
    {
        $this->actingAs($this->admin)
            ->get(route('admin.vendors.create'))
            ->assertInertia(fn ($page) => $page
                ->has('options.documents', 6)
                ->where('options.documents.1.value', 'public_liability')
                ->where('options.documents.1.categories', null)
                ->where('options.documents.5.value', 'electrical_registration')
                ->where('options.documents.5.categories', ['electrical', 'kitchen_renovation', 'bathroom_renovation'])
            );
    }

    public function test_admins_can_record_a_vendors_documents()
    {
        $this->actingAs($this->admin)
            ->post(route('admin.vendors.store'), [
                'name' => 'Brightline Electrical',
                'type' => 'general',
                'abn' => '51 824 753 556',
                'public_liability_policy_number' => 'PL-1',
                'public_liability_cover' => 5_000_000,
                'public_liability_expires_on' => '2027-06-30',
                'electrical_registration_number' => 'REC 12345',
                'electrical_registration_expires_on' => '2027-01-31',
                'documents_checked_on' => today()->toDateString(),
            ])
            ->assertRedirect(route('admin.vendors.index'));

        $vendor = Vendor::sole();
        $this->assertSame('REC 12345', $vendor->electrical_registration_number);
        $this->assertSame('2027-06-30', $vendor->public_liability_expires_on->toDateString());

        $this->actingAs($this->admin)
            ->get(route('admin.vendors.edit', $vendor))
            ->assertInertia(fn ($page) => $page->where('vendor.electrical_registration_expires_on', '2027-01-31'));
    }

    public function test_a_licence_or_policy_number_needs_an_expiry_date()
    {
        $this->actingAs($this->admin)
            ->post(route('admin.vendors.store'), [
                'name' => 'Northside Plumbing',
                'type' => 'general',
                'plumbing_licence_number' => '54321',
                'workcover_policy_number' => '12345678',
            ])
            ->assertSessionHasErrors(['plumbing_licence_expires_on', 'workcover_expires_on']);
    }

    public function test_electrical_contractors_need_at_least_five_million_in_cover()
    {
        $vendor = [
            'name' => 'Brightline Electrical',
            'type' => 'general',
            'electrical_registration_number' => 'REC 12345',
            'electrical_registration_expires_on' => '2027-01-31',
        ];

        $this->actingAs($this->admin)
            ->post(route('admin.vendors.store'), $vendor)
            ->assertSessionHasErrors('public_liability_cover');

        $this->actingAs($this->admin)
            ->post(route('admin.vendors.store'), [...$vendor, 'public_liability_cover' => 4_999_999])
            ->assertSessionHasErrors('public_liability_cover');

        $this->assertSame(0, Vendor::count());
    }

    public function test_an_invalid_abn_is_rejected()
    {
        $this->actingAs($this->admin)
            ->post(route('admin.vendors.store'), ['name' => 'Test', 'type' => 'general', 'abn' => '123'])
            ->assertSessionHasErrors('abn');
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
