<?php

namespace Tests\Feature\Admin;

use App\Enums\JobRequestStatus;
use App\Enums\JobUrgency;
use App\Models\JobRequest;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->admin = User::factory()->admin()->create();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function request(string $title, JobRequestStatus $status, array $attributes = []): JobRequest
    {
        return JobRequest::factory()->create(['title' => $title, 'status' => $status, ...$attributes]);
    }

    public function test_new_requests_only_appear_in_the_new_section()
    {
        $this->request('New', JobRequestStatus::Submitted);

        $this->actingAs($this->admin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/dashboard')
                ->where('newRequests.total', 1)
                ->where('newRequests.items.0.title', 'New')
                ->where('unassignedRequests.total', 0)
            );
    }

    public function test_only_open_reviewed_requests_without_a_vendor_need_one()
    {
        $this->request('Reviewing', JobRequestStatus::Reviewing);
        $this->request('Quoted', JobRequestStatus::Quoted);
        $this->request('Scheduled', JobRequestStatus::Scheduled);
        $this->request('Completed', JobRequestStatus::Completed);
        $this->request('Cancelled', JobRequestStatus::Cancelled);
        $this->request('Deleted', JobRequestStatus::Reviewing)->delete();
        $this->request('Assigned', JobRequestStatus::Reviewing)->assignVendor(Vendor::factory()->create());

        $this->actingAs($this->admin)
            ->get(route('admin.index'))
            ->assertInertia(fn ($page) => $page
                ->where('newRequests.total', 0)
                ->where('unassignedRequests.total', 3)
            );
    }

    public function test_emergencies_come_first_then_the_oldest()
    {
        $this->request('Newer', JobRequestStatus::Submitted, ['urgency' => JobUrgency::Flexible, 'created_at' => now()->subDay()]);
        $this->request('Older', JobRequestStatus::Submitted, ['urgency' => JobUrgency::Flexible, 'created_at' => now()->subWeek()]);
        $this->request('Emergency', JobRequestStatus::Submitted, ['urgency' => JobUrgency::Emergency]);

        $this->actingAs($this->admin)
            ->get(route('admin.index'))
            ->assertInertia(fn ($page) => $page
                ->where('newRequests.items.0.title', 'Emergency')
                ->where('newRequests.items.1.title', 'Older')
                ->where('newRequests.items.2.title', 'Newer')
            );
    }

    public function test_sections_list_a_limited_number_but_count_them_all()
    {
        JobRequest::factory()->count(9)->create(['status' => JobRequestStatus::Submitted]);

        $this->actingAs($this->admin)
            ->get(route('admin.index'))
            ->assertInertia(fn ($page) => $page
                ->where('newRequests.total', 9)
                ->has('newRequests.items', 8)
            );
    }

    public function test_vendors_with_document_problems_are_listed_most_serious_first()
    {
        Vendor::factory()->withDocuments()->create(['name' => 'Up To Date']);
        Vendor::factory()->withDocuments()->create(['name' => 'A Expiring', 'workcover_expires_on' => now()->addDays(10)]);
        Vendor::factory()->withDocuments()->create(['name' => 'B Expired', 'public_liability_expires_on' => now()->subDays(10)]);
        Vendor::factory()->withDocuments()->create(['name' => 'Archived', 'public_liability_expires_on' => now()->subDays(10)])->delete();

        $this->actingAs($this->admin)
            ->get(route('admin.index'))
            ->assertInertia(fn ($page) => $page
                ->where('vendorDocuments.action_needed', 1)
                ->where('vendorDocuments.expiring_soon', 1)
                ->has('vendorDocuments.items', 2)
                ->where('vendorDocuments.items.0.name', 'B Expired')
                ->where('vendorDocuments.items.1.name', 'A Expiring')
            );
    }

    public function test_the_request_list_can_show_requests_that_need_a_vendor()
    {
        $this->request('New', JobRequestStatus::Submitted);
        $this->request('Needs a vendor', JobRequestStatus::Reviewing);
        $this->request('Assigned', JobRequestStatus::Reviewing)->assignVendor(Vendor::factory()->create());

        $this->actingAs($this->admin)
            ->get(route('admin.job-requests.index', ['vendor' => 'needs_vendor']))
            ->assertInertia(fn ($page) => $page
                ->has('jobRequests.data', 1)
                ->where('jobRequests.data.0.title', 'Needs a vendor')
                ->where('filters.vendor', 'needs_vendor')
            );

        $this->actingAs($this->admin)
            ->get(route('admin.job-requests.index', ['vendor' => 'assigned']))
            ->assertInertia(fn ($page) => $page
                ->has('jobRequests.data', 1)
                ->where('jobRequests.data.0.title', 'Assigned')
            );
    }
}
