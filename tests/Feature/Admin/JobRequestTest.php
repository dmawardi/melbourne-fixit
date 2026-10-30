<?php

namespace Tests\Feature\Admin;

use App\Enums\ActivityEvent;
use App\Enums\JobRequestStatus;
use App\Models\JobRequest;
use App\Models\JobRequestFile;
use App\Models\JobRequestMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admins_can_list_all_requests_most_recently_active_first()
    {
        $older = JobRequest::factory()->create(['last_activity_at' => now()->subDays(3)]);
        $newest = JobRequest::factory()->create(['last_activity_at' => now()]);
        $middle = JobRequest::factory()->claimedBy(User::factory()->create())->create(['last_activity_at' => now()->subDay()]);

        $this->actingAs($this->admin)
            ->get(route('admin.job-requests.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/job-requests/index')
                ->has('jobRequests.data', 3)
                ->where('jobRequests.data.0.id', $newest->public_id)
                ->where('jobRequests.data.1.id', $middle->public_id)
                ->where('jobRequests.data.2.id', $older->public_id)
            );
    }

    public function test_the_index_includes_the_latest_activity()
    {
        $jobRequest = JobRequest::factory()->create();
        $jobRequest->recordActivity(ActivityEvent::Created);
        $jobRequest->recordActivity(ActivityEvent::MessagePosted, ['excerpt' => 'Handyman confirmed'], $this->admin);

        $this->actingAs($this->admin)
            ->get(route('admin.job-requests.index'))
            ->assertInertia(fn ($page) => $page
                ->where('jobRequests.data.0.latest_activity.event.value', 'message_posted')
                ->where('jobRequests.data.0.latest_activity.user.name', $this->admin->name)
            );
    }

    public function test_admins_can_filter_and_search_requests()
    {
        JobRequest::factory()->create(['title' => 'Leaking tap', 'suburb' => 'Fitzroy']);
        JobRequest::factory()->status(JobRequestStatus::Quoted)->create(['title' => 'Fence repair', 'suburb' => 'Coburg']);
        JobRequest::factory()->claimedBy(User::factory()->create())->create(['title' => 'Paint lounge', 'suburb' => 'Richmond']);

        $this->actingAs($this->admin)
            ->get(route('admin.job-requests.index', ['search' => 'coburg']))
            ->assertInertia(fn ($page) => $page->has('jobRequests.data', 1)->where('jobRequests.data.0.title', 'Fence repair'));

        $this->actingAs($this->admin)
            ->get(route('admin.job-requests.index', ['status' => 'quoted']))
            ->assertInertia(fn ($page) => $page->has('jobRequests.data', 1)->where('jobRequests.data.0.title', 'Fence repair'));

        $this->actingAs($this->admin)
            ->get(route('admin.job-requests.index', ['claimed' => 'yes']))
            ->assertInertia(fn ($page) => $page->has('jobRequests.data', 1)->where('jobRequests.data.0.title', 'Paint lounge'));

        $this->actingAs($this->admin)
            ->get(route('admin.job-requests.index', ['status' => 'not-a-status']))
            ->assertInertia(fn ($page) => $page->has('jobRequests.data', 3)->where('filters.status', null));
    }

    public function test_admins_can_view_everything_on_a_request()
    {
        $jobRequest = JobRequest::factory()->claimedBy(User::factory()->create())->create(['admin_notes' => 'Gate code 4821']);
        JobRequestFile::factory()->internal()->for($jobRequest)->create();
        JobRequestFile::factory()->shared()->for($jobRequest)->create();
        JobRequestMessage::factory()->for($jobRequest)->create(['user_id' => $this->admin->id]);
        JobRequestMessage::factory()->shared()->for($jobRequest)->create();
        $jobRequest->recordActivity(ActivityEvent::Created);

        $this->actingAs($this->admin)
            ->get(route('admin.job-requests.show', $jobRequest))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/job-requests/show')
                ->where('jobRequest.admin_notes', 'Gate code 4821')
                ->has('jobRequest.files', 2)
                ->has('jobRequest.files.0.visibility')
                ->has('jobRequest.messages', 2)
                ->has('jobRequest.activities', 1)
                ->has('jobRequest.owner.email')
                ->has('options.visibilities', 2)
            );
    }

    public function test_admins_can_change_status_and_notes_and_each_is_logged()
    {
        $jobRequest = JobRequest::factory()->create();

        $this->actingAs($this->admin)
            ->patch(route('admin.job-requests.update', $jobRequest), [
                'status' => 'reviewing',
                'admin_notes' => 'Customer prefers phone contact.',
                'address_line' => '12 Smith Street',
            ])
            ->assertRedirect(route('admin.job-requests.show', $jobRequest));

        $jobRequest->refresh();
        $this->assertSame(JobRequestStatus::Reviewing, $jobRequest->status);
        $this->assertSame('Customer prefers phone contact.', $jobRequest->admin_notes);

        $activities = $jobRequest->activities()->get()->keyBy(fn ($activity) => $activity->event->value);
        $this->assertCount(3, $activities);
        $this->assertSame(['from' => 'submitted', 'to' => 'reviewing'], $activities['status_changed']->properties);
        $this->assertSame(['from' => null, 'to' => 'Customer prefers phone contact.'], $activities['notes_updated']->properties);
        $this->assertSame(['address_line'], array_keys($activities['updated']->properties['changes']));
        $this->assertSame($this->admin->id, $activities['status_changed']->user_id);
        $this->assertNotNull($jobRequest->last_activity_at);
    }

    public function test_unchanged_updates_are_not_logged()
    {
        $jobRequest = JobRequest::factory()->create();

        $this->actingAs($this->admin)
            ->patch(route('admin.job-requests.update', $jobRequest), ['status' => 'submitted']);

        $this->assertSame(0, $jobRequest->activities()->count());
    }

    public function test_admins_cannot_change_the_contact_email()
    {
        $jobRequest = JobRequest::factory()->create(['contact_email' => 'jane@example.com']);

        $this->actingAs($this->admin)
            ->patch(route('admin.job-requests.update', $jobRequest), ['contact_email' => 'other@example.com']);

        $this->assertSame('jane@example.com', $jobRequest->fresh()->contact_email);
    }

    public function test_admins_can_edit_requests_regardless_of_status()
    {
        $jobRequest = JobRequest::factory()->status(JobRequestStatus::Completed)->create();

        $this->actingAs($this->admin)
            ->patch(route('admin.job-requests.update', $jobRequest), ['title' => 'Updated title'])
            ->assertRedirect();

        $this->assertSame('Updated title', $jobRequest->fresh()->title);
    }

    public function test_admins_can_delete_requests_and_it_is_logged()
    {
        $jobRequest = JobRequest::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.job-requests.destroy', $jobRequest))
            ->assertRedirect(route('admin.job-requests.index'));

        $this->assertSoftDeleted($jobRequest);
        $this->assertSame(ActivityEvent::Deleted, $jobRequest->activities()->first()->event);
        $this->assertNotNull($jobRequest->fresh()->last_activity_at);
    }
}
