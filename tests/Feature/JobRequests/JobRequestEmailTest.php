<?php

namespace Tests\Feature\JobRequests;

use App\Enums\JobRequestStatus;
use App\Enums\JobUrgency;
use App\Models\JobRequest;
use App\Models\User;
use App\Notifications\JobRequestReceived;
use App\Notifications\NewJobRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class JobRequestEmailTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->admin = User::factory()->admin()->create();
        Notification::fake();
    }

    /**
     * @return array<string, string>
     */
    private function validPayload(array $overrides = []): array
    {
        return [
            'contact_name' => 'Spam Name',
            'contact_email' => 'jane@example.com',
            'contact_phone' => '0412 345 678',
            'suburb' => 'Spam Suburb',
            'postcode' => '3065',
            'category' => 'plumbing',
            'title' => 'Spam Title',
            'description' => 'Spam Description that is long enough to pass validation.',
            'urgency' => 'within_a_week',
            ...$overrides,
        ];
    }

    private function customerMail(array $overrides = []): string
    {
        $this->post(route('job-requests.store'), $this->validPayload($overrides));

        $mail = null;

        Notification::assertSentOnDemand(JobRequestReceived::class, function (JobRequestReceived $notification) use (&$mail): bool {
            $mail = $notification->toMail(new AnonymousNotifiable);

            return true;
        });

        return implode("\n", [$mail->subject, ...$mail->introLines, ...$mail->outroLines]);
    }

    public function test_a_guest_request_emails_every_admin_and_the_customer()
    {
        $otherAdmin = User::factory()->admin()->create();
        $customer = User::factory()->create();

        $this->post(route('job-requests.store'), $this->validPayload());

        Notification::assertSentTo([$this->admin, $otherAdmin], NewJobRequest::class);
        Notification::assertNotSentTo($customer, NewJobRequest::class);
        Notification::assertSentOnDemand(
            JobRequestReceived::class,
            fn ($notification, $channels, $notifiable): bool => $notifiable->routes['mail'] === 'jane@example.com',
        );
    }

    public function test_a_logged_in_customers_request_sends_the_same_emails()
    {
        $this->actingAs(User::factory()->create())
            ->post(route('job-requests.store'), $this->validPayload());

        Notification::assertSentTo($this->admin, NewJobRequest::class);
        Notification::assertSentOnDemand(JobRequestReceived::class);
    }

    public function test_a_rejected_submission_sends_nothing()
    {
        $this->post(route('job-requests.store'), $this->validPayload(['website' => 'bot']));

        Notification::assertNothingSent();
    }

    public function test_admin_emails_mark_emergencies()
    {
        $emergency = JobRequest::factory()->create(['title' => 'Burst pipe', 'suburb' => 'Fitzroy', 'urgency' => JobUrgency::Emergency]);
        $routine = JobRequest::factory()->create(['title' => 'Paint room', 'suburb' => 'Fitzroy', 'urgency' => JobUrgency::Flexible]);

        $this->assertSame('EMERGENCY: Burst pipe in Fitzroy', (new NewJobRequest($emergency))->toMail($this->admin)->subject);
        $this->assertSame('New request: Paint room in Fitzroy', (new NewJobRequest($routine))->toMail($this->admin)->subject);
    }

    public function test_the_customer_email_contains_nothing_the_sender_typed()
    {
        $mail = $this->customerMail();

        $this->assertStringContainsString('your plumbing request', $mail);
        $this->assertStringNotContainsString('Spam', $mail);
    }

    public function test_the_customer_email_sets_expectations_for_emergencies()
    {
        config(['business.phone' => '03 9000 0000']);

        $mail = $this->customerMail(['urgency' => 'emergency']);

        $this->assertStringContainsString('within 12 hours on nights and weekends', $mail);
        $this->assertStringContainsString('call 000 first', $mail);
        $this->assertStringContainsString('Need us in a hurry? Call 03 9000 0000.', $mail);
    }

    public function test_the_customer_email_leaves_out_the_number_if_there_is_none()
    {
        config(['business.phone' => null]);

        $this->assertStringNotContainsString('in a hurry', $this->customerMail());
    }

    public function test_the_submitted_page_knows_about_emergencies()
    {
        $this->post(route('job-requests.store'), $this->validPayload(['urgency' => 'emergency']));

        $this->get(route('job-requests.submitted'))
            ->assertInertia(fn ($page) => $page->where('emergency', true));
    }

    public function test_admins_are_alerted_when_a_customer_changes_a_request_to_an_emergency()
    {
        $customer = User::factory()->create();
        $jobRequest = JobRequest::factory()->claimedBy($customer)->create([
            'status' => JobRequestStatus::Submitted,
            'urgency' => JobUrgency::Flexible,
        ]);
        $payload = $this->validPayload(['urgency' => 'emergency']);

        $this->actingAs($customer)->patch(route('job-requests.update', $jobRequest), $payload)->assertSessionHasNoErrors();
        Notification::assertSentToTimes($this->admin, NewJobRequest::class, 1);

        // Already an emergency, so saving again doesn't alert anyone.
        $this->actingAs($customer)->patch(route('job-requests.update', $jobRequest), $payload)->assertSessionHasNoErrors();
        Notification::assertSentToTimes($this->admin, NewJobRequest::class, 1);
    }

    public function test_admin_edits_do_not_send_an_alert()
    {
        $jobRequest = JobRequest::factory()->create(['urgency' => JobUrgency::Flexible]);

        $this->actingAs($this->admin)
            ->patch(route('admin.job-requests.update', $jobRequest), ['urgency' => 'emergency'])
            ->assertSessionHasNoErrors();

        $this->assertSame(JobUrgency::Emergency, $jobRequest->fresh()->urgency);
        Notification::assertNothingSent();
    }

    public function test_one_email_address_can_only_be_sent_so_many_requests_a_day()
    {
        foreach (range(1, 10) as $attempt) {
            $this->withServerVariables(['REMOTE_ADDR' => "10.0.0.{$attempt}"])
                ->post(route('job-requests.store'), $this->validPayload())
                ->assertRedirect();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.99'])
            ->post(route('job-requests.store'), $this->validPayload(['contact_email' => 'JANE@example.com']))
            ->assertTooManyRequests();
    }
}
