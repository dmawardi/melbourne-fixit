<?php

namespace Tests\Feature\Admin;

use App\Enums\ActivityEvent;
use App\Enums\JobCategory;
use App\Models\JobRequest;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobRequestVendorTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admins_can_assign_a_vendor_and_it_is_logged()
    {
        $jobRequest = JobRequest::factory()->create(['category' => JobCategory::Plumbing, 'last_activity_at' => now()->subWeek()]);
        $vendor = Vendor::factory()->specialist(JobCategory::Plumbing)->create(['name' => 'Northside Plumbing']);
        $this->freezeSecond();

        $this->actingAs($this->admin)
            ->put(route('admin.job-requests.vendor.update', $jobRequest), ['vendor_id' => $vendor->id])
            ->assertRedirect(route('admin.job-requests.show', $jobRequest));

        $this->assertSame($vendor->id, $jobRequest->fresh()->vendor_id);
        $this->assertTrue($jobRequest->fresh()->last_activity_at->equalTo(now()));

        $activity = $jobRequest->activities()->sole();
        $this->assertSame(ActivityEvent::VendorAssigned, $activity->event);
        $this->assertSame($this->admin->id, $activity->user_id);
        $this->assertSame([
            'vendor_id' => $vendor->id,
            'vendor_name' => 'Northside Plumbing',
            'vendor_type' => 'specialist',
            'outside_categories' => false,
        ], $activity->properties);
    }

    public function test_admins_can_remove_a_vendor_and_it_is_logged()
    {
        $jobRequest = JobRequest::factory()->create();
        $vendor = Vendor::factory()->create(['name' => 'Bayside Building']);
        $jobRequest->assignVendor($vendor);

        $this->actingAs($this->admin)
            ->delete(route('admin.job-requests.vendor.destroy', $jobRequest))
            ->assertRedirect(route('admin.job-requests.show', $jobRequest));

        $this->assertNull($jobRequest->fresh()->vendor_id);

        $activity = $jobRequest->activities()->where('event', ActivityEvent::VendorUnassigned)->sole();
        $this->assertSame($this->admin->id, $activity->user_id);
        $this->assertSame('Bayside Building', $activity->properties['vendor_name']);
    }

    public function test_removing_when_no_vendor_is_assigned_logs_nothing()
    {
        $jobRequest = JobRequest::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.job-requests.vendor.destroy', $jobRequest))
            ->assertRedirect();

        $this->assertSame(0, $jobRequest->activities()->count());
    }

    public function test_changing_vendor_logs_the_removal_and_the_assignment()
    {
        $jobRequest = JobRequest::factory()->create();
        $first = Vendor::factory()->create(['name' => 'First Vendor']);
        $second = Vendor::factory()->create(['name' => 'Second Vendor']);
        $jobRequest->assignVendor($first);
        $jobRequest->activities()->delete();

        $this->actingAs($this->admin)
            ->put(route('admin.job-requests.vendor.update', $jobRequest), ['vendor_id' => $second->id]);

        $this->assertSame($second->id, $jobRequest->fresh()->vendor_id);

        $activities = $jobRequest->activities()->reorder('id')->get();
        $this->assertSame(
            [ActivityEvent::VendorUnassigned, ActivityEvent::VendorAssigned],
            $activities->map->event->all(),
        );
        $this->assertSame('First Vendor', $activities[0]->properties['vendor_name']);
        $this->assertSame('Second Vendor', $activities[1]->properties['vendor_name']);
    }

    public function test_assigning_the_same_vendor_again_logs_nothing()
    {
        $jobRequest = JobRequest::factory()->create();
        $vendor = Vendor::factory()->create();
        $jobRequest->assignVendor($vendor);
        $jobRequest->activities()->delete();

        $this->actingAs($this->admin)
            ->put(route('admin.job-requests.vendor.update', $jobRequest), ['vendor_id' => $vendor->id]);

        $this->assertSame(0, $jobRequest->activities()->count());
    }

    public function test_specialists_can_be_assigned_outside_their_categories_and_it_is_flagged()
    {
        $jobRequest = JobRequest::factory()->create(['category' => JobCategory::Roofing]);
        $vendor = Vendor::factory()->specialist(JobCategory::Plumbing)->create();

        $this->actingAs($this->admin)
            ->put(route('admin.job-requests.vendor.update', $jobRequest), ['vendor_id' => $vendor->id])
            ->assertSessionHasNoErrors();

        $this->assertSame($vendor->id, $jobRequest->fresh()->vendor_id);
        $this->assertTrue($jobRequest->activities()->sole()->properties['outside_categories']);
    }

    public function test_the_request_page_shows_whether_vendors_handle_the_category()
    {
        $jobRequest = JobRequest::factory()->create(['category' => JobCategory::Roofing]);
        $plumber = Vendor::factory()->specialist(JobCategory::Plumbing)->create(['name' => 'B Plumber']);
        $general = Vendor::factory()->general()->create(['name' => 'A General']);
        Vendor::factory()->create()->delete();
        $jobRequest->assignVendor($plumber);

        $this->actingAs($this->admin)
            ->get(route('admin.job-requests.show', $jobRequest))
            ->assertInertia(fn ($page) => $page
                ->where('jobRequest.vendor.id', $plumber->id)
                ->where('jobRequest.vendor.handles_category', false)
                ->has('vendors', 2)
                ->where('vendors.0.id', $general->id)
                ->where('vendors.0.handles_category', true)
                ->where('vendors.1.id', $plumber->id)
                ->where('vendors.1.handles_category', false)
            );
    }

    public function test_changing_the_category_keeps_the_vendor_and_shows_the_mismatch()
    {
        $jobRequest = JobRequest::factory()->create(['category' => JobCategory::Plumbing]);
        $jobRequest->assignVendor(Vendor::factory()->specialist(JobCategory::Plumbing)->create());

        $this->actingAs($this->admin)
            ->patch(route('admin.job-requests.update', $jobRequest), ['category' => 'roofing']);

        $this->actingAs($this->admin)
            ->get(route('admin.job-requests.show', $jobRequest))
            ->assertInertia(fn ($page) => $page->where('jobRequest.vendor.handles_category', false));
    }

    public function test_archived_vendors_cannot_be_assigned()
    {
        $jobRequest = JobRequest::factory()->create();
        $vendor = Vendor::factory()->create();
        $vendor->delete();

        $this->actingAs($this->admin)
            ->put(route('admin.job-requests.vendor.update', $jobRequest), ['vendor_id' => $vendor->id])
            ->assertSessionHasErrors('vendor_id');

        $this->assertNull($jobRequest->fresh()->vendor_id);
    }

    public function test_archived_vendors_stay_on_existing_requests()
    {
        $jobRequest = JobRequest::factory()->create();
        $vendor = Vendor::factory()->create(['name' => 'Old Vendor']);
        $jobRequest->assignVendor($vendor);
        $vendor->delete();

        $this->actingAs($this->admin)
            ->get(route('admin.job-requests.show', $jobRequest))
            ->assertInertia(fn ($page) => $page
                ->where('jobRequest.vendor.name', 'Old Vendor')
                ->where('jobRequest.vendor.archived', true)
                ->has('vendors', 0)
            );

        $this->assertSame(1, $jobRequest->activities()->count());
    }

    public function test_archived_vendors_can_be_removed_from_requests()
    {
        $jobRequest = JobRequest::factory()->create();
        $vendor = Vendor::factory()->create();
        $jobRequest->assignVendor($vendor);
        $vendor->delete();

        $this->actingAs($this->admin)->delete(route('admin.job-requests.vendor.destroy', $jobRequest));

        $this->assertNull($jobRequest->fresh()->vendor_id);
    }
}
