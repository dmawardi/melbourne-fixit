<?php

namespace Tests\Feature\JobRequests;

use App\Enums\ActivityEvent;
use App\Enums\FileKind;
use App\Enums\JobRequestStatus;
use App\Models\JobRequest;
use App\Models\JobRequestFile;
use App\Models\JobRequestMessage;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * @return array<string, string>
     */
    private function validPayload(array $overrides = []): array
    {
        return [
            'contact_name' => 'Jane Citizen',
            'contact_email' => 'Jane@Example.com',
            'contact_phone' => '0412 345 678',
            'suburb' => 'Fitzroy',
            'postcode' => '3065',
            'category' => 'plumbing',
            'title' => 'Leaking kitchen tap',
            'description' => 'The kitchen mixer tap has been dripping for a week now.',
            'urgency' => 'within_a_week',
            ...$overrides,
        ];
    }

    public function test_the_request_form_can_be_rendered()
    {
        $this->get(route('job-requests.create'))->assertOk();
    }

    public function test_guests_can_submit_a_request()
    {
        $response = $this->post(route('job-requests.store'), $this->validPayload());

        $response->assertRedirect(route('job-requests.submitted'));
        $response->assertSessionHas('submittedEmail', 'jane@example.com');

        $jobRequest = JobRequest::sole();
        $this->assertNull($jobRequest->user_id);
        $this->assertSame('jane@example.com', $jobRequest->contact_email);
        $this->assertSame(JobRequestStatus::Submitted, $jobRequest->status);
        $this->assertNotEmpty($jobRequest->public_id);

        $activity = $jobRequest->activities()->sole();
        $this->assertSame(ActivityEvent::Created, $activity->event);
        $this->assertNull($activity->user_id);
        $this->assertNotNull($jobRequest->fresh()->last_activity_at);
    }

    public function test_the_honeypot_rejects_bots()
    {
        $this->post(route('job-requests.store'), $this->validPayload(['website' => 'http://spam.test']))
            ->assertSessionHasErrors('website');

        $this->assertDatabaseCount('job_requests', 0);
    }

    public function test_non_victorian_postcodes_are_rejected()
    {
        $this->post(route('job-requests.store'), $this->validPayload(['postcode' => '2000']))
            ->assertSessionHasErrors('postcode');
    }

    public function test_submissions_are_rate_limited()
    {
        foreach (range(1, 3) as $i) {
            $this->post(route('job-requests.store'), $this->validPayload())->assertRedirect();
        }

        $this->post(route('job-requests.store'), $this->validPayload())->assertTooManyRequests();
    }

    public function test_verified_users_own_their_submissions_immediately()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('job-requests.store'), $this->validPayload());

        $jobRequest = JobRequest::sole();
        $response->assertRedirect(route('job-requests.show', $jobRequest));
        $this->assertTrue($jobRequest->user->is($user));
    }

    public function test_requests_are_claimed_when_a_verified_user_logs_in()
    {
        $user = User::factory()->create(['email' => 'jane@example.com']);
        $jobRequest = JobRequest::factory()->create(['contact_email' => 'JANE@example.com']);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

        $this->assertTrue($jobRequest->fresh()->user->is($user));
        $this->assertNotNull($jobRequest->fresh()->claimed_at);

        $activity = $jobRequest->activities()->sole();
        $this->assertSame(ActivityEvent::Claimed, $activity->event);
        $this->assertSame($user->id, $activity->user_id);
    }

    public function test_requests_are_not_claimed_by_unverified_users()
    {
        $user = User::factory()->unverified()->create(['email' => 'jane@example.com']);
        $jobRequest = JobRequest::factory()->create(['contact_email' => 'jane@example.com']);

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

        $this->assertNull($jobRequest->fresh()->user_id);
    }

    public function test_requests_are_claimed_when_email_is_verified()
    {
        $user = User::factory()->create(['email' => 'jane@example.com']);
        $jobRequest = JobRequest::factory()->create(['contact_email' => 'jane@example.com']);

        event(new Verified($user));

        $this->assertTrue($jobRequest->fresh()->user->is($user));
    }

    public function test_already_claimed_requests_are_not_reassigned()
    {
        $owner = User::factory()->create();
        $other = User::factory()->create(['email' => 'jane@example.com']);
        $jobRequest = JobRequest::factory()->claimedBy($owner)->create(['contact_email' => 'jane@example.com']);

        event(new Verified($other));

        $this->assertTrue($jobRequest->fresh()->user->is($owner));
    }

    public function test_users_can_list_their_requests()
    {
        $user = User::factory()->create();
        JobRequest::factory()->claimedBy($user)->count(2)->create();
        JobRequest::factory()->create();

        $this->actingAs($user)
            ->get(route('job-requests.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('jobRequests.data', 2));
    }

    public function test_users_can_view_their_own_request()
    {
        $user = User::factory()->create();
        $jobRequest = JobRequest::factory()->claimedBy($user)->create();

        $this->actingAs($user)
            ->get(route('job-requests.show', $jobRequest))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('jobRequest.id', $jobRequest->public_id)
                ->missing('jobRequest.submitted_ip')
            );
    }

    public function test_users_cannot_view_other_users_requests()
    {
        $jobRequest = JobRequest::factory()->claimedBy(User::factory()->create())->create();

        $this->actingAs(User::factory()->create())
            ->get(route('job-requests.show', $jobRequest))
            ->assertForbidden();
    }

    public function test_users_cannot_view_unclaimed_requests()
    {
        $jobRequest = JobRequest::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('job-requests.show', $jobRequest))
            ->assertForbidden();
    }

    public function test_users_can_update_their_request()
    {
        $user = User::factory()->create();
        $jobRequest = JobRequest::factory()->claimedBy($user)->create();

        $this->actingAs($user)
            ->patch(route('job-requests.update', $jobRequest), [
                'address_line' => '12 Smith Street',
                'contact_email' => 'attacker@example.com',
            ])
            ->assertRedirect(route('job-requests.show', $jobRequest));

        $jobRequest->refresh();
        $this->assertSame('12 Smith Street', $jobRequest->address_line);
        $this->assertSame($user->email, $jobRequest->contact_email);

        $activity = $jobRequest->activities()->sole();
        $this->assertSame(ActivityEvent::Updated, $activity->event);
        $this->assertSame('12 Smith Street', $activity->properties['changes']['address_line']['to']);
        $this->assertArrayNotHasKey('contact_email', $activity->properties['changes']);
    }

    public function test_customers_cannot_change_status_or_admin_notes()
    {
        $user = User::factory()->create();
        $jobRequest = JobRequest::factory()->claimedBy($user)->create();

        $this->actingAs($user)->patch(route('job-requests.update', $jobRequest), [
            'status' => 'completed',
            'admin_notes' => 'Injected',
        ]);

        $jobRequest->refresh();
        $this->assertSame(JobRequestStatus::Submitted, $jobRequest->status);
        $this->assertNull($jobRequest->admin_notes);
    }

    public function test_customers_only_see_shared_files_and_messages()
    {
        $user = User::factory()->create();
        $jobRequest = JobRequest::factory()->claimedBy($user)->create(['admin_notes' => 'Secret note']);
        $sharedQuote = JobRequestFile::factory()->pdf(FileKind::ClientQuote)->shared()->for($jobRequest)->create();
        JobRequestFile::factory()->pdf(FileKind::VendorQuote)->internal()->for($jobRequest)->create();
        $sharedMessage = JobRequestMessage::factory()->shared()->for($jobRequest)->create(['body' => 'Your quote is ready']);
        JobRequestMessage::factory()->for($jobRequest)->create(['body' => 'Vendor charges $400, we quote $550']);
        $jobRequest->recordActivity(ActivityEvent::NotesUpdated);

        $response = $this->actingAs($user)->get(route('job-requests.show', $jobRequest));

        $response->assertInertia(fn ($page) => $page
            ->has('jobRequest.files', 1)
            ->where('jobRequest.files.0.id', $sharedQuote->id)
            ->missing('jobRequest.files.0.visibility')
            ->has('jobRequest.messages', 1)
            ->where('jobRequest.messages.0.id', $sharedMessage->id)
            ->missing('jobRequest.messages.0.author')
            ->missing('jobRequest.admin_notes')
            ->missing('jobRequest.activities')
        );
        $response->assertDontSee('Secret note');
        $response->assertDontSee('Vendor charges');
    }

    public function test_requests_cannot_be_updated_once_quoted()
    {
        $user = User::factory()->create();
        $jobRequest = JobRequest::factory()->claimedBy($user)->status(JobRequestStatus::Quoted)->create();

        $this->actingAs($user)
            ->patch(route('job-requests.update', $jobRequest), ['title' => 'Changed title'])
            ->assertForbidden();
    }

    public function test_users_can_delete_their_request()
    {
        $user = User::factory()->create();
        $jobRequest = JobRequest::factory()->claimedBy($user)->create();

        $this->actingAs($user)
            ->delete(route('job-requests.destroy', $jobRequest))
            ->assertRedirect(route('job-requests.index'));

        $this->assertSoftDeleted($jobRequest);
    }
}
