<?php

namespace Tests\Feature\Admin;

use App\Enums\ActivityEvent;
use App\Enums\Visibility;
use App\Models\JobRequest;
use App\Models\JobRequestMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobRequestMessageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_admins_can_post_internal_updates()
    {
        $jobRequest = JobRequest::factory()->create(['last_activity_at' => now()->subWeek()]);
        $this->freezeSecond();

        $this->actingAs($this->admin)
            ->post(route('admin.job-requests.messages.store', $jobRequest), [
                'body' => 'Handyman confirmed visit today at 3PM',
                'visibility' => 'internal',
            ])
            ->assertRedirect(route('admin.job-requests.show', $jobRequest));

        $message = $jobRequest->messages()->sole();
        $this->assertSame('Handyman confirmed visit today at 3PM', $message->body);
        $this->assertSame(Visibility::Internal, $message->visibility);
        $this->assertTrue($message->author->is($this->admin));
        $this->assertNotNull($message->created_at);

        $this->assertTrue($jobRequest->fresh()->last_activity_at->equalTo(now()));
        $activity = $jobRequest->activities()->sole();
        $this->assertSame(ActivityEvent::MessagePosted, $activity->event);
        $this->assertSame('Handyman confirmed visit today at 3PM', $activity->properties['excerpt']);
    }

    public function test_admins_can_share_updates_with_the_customer()
    {
        $jobRequest = JobRequest::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.job-requests.messages.store', $jobRequest), [
                'body' => 'Your job is booked in for Thursday.',
                'visibility' => 'customer',
            ]);

        $this->assertSame(Visibility::Customer, $jobRequest->messages()->sole()->visibility);
    }

    public function test_messages_require_a_body_and_visibility()
    {
        $jobRequest = JobRequest::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.job-requests.messages.store', $jobRequest), [])
            ->assertSessionHasErrors(['body', 'visibility']);
    }

    public function test_admins_can_delete_updates_and_it_is_logged()
    {
        $jobRequest = JobRequest::factory()->create();
        $message = JobRequestMessage::factory()->for($jobRequest)->create(['user_id' => User::factory()->admin()->create()->id]);

        $this->actingAs($this->admin)
            ->delete(route('admin.job-requests.messages.destroy', [$jobRequest, $message]))
            ->assertRedirect(route('admin.job-requests.show', $jobRequest));

        $this->assertModelMissing($message);
        $activity = $jobRequest->activities()->sole();
        $this->assertSame(ActivityEvent::MessageDeleted, $activity->event);
        $this->assertSame($this->admin->id, $activity->user_id);
    }

    public function test_messages_from_another_request_are_not_found()
    {
        $jobRequest = JobRequest::factory()->create();
        $otherMessage = JobRequestMessage::factory()->create();

        $this->actingAs($this->admin)
            ->delete(route('admin.job-requests.messages.destroy', [$jobRequest, $otherMessage]))
            ->assertNotFound();
    }
}
