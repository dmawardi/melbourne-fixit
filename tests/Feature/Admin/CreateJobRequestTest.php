<?php

namespace Tests\Feature\Admin;

use App\Enums\ActivityEvent;
use App\Enums\JobCategory;
use App\Enums\JobRequestStatus;
use App\Enums\JobUrgency;
use App\Models\JobRequest;
use App\Models\User;
use App\Notifications\CustomerAccountCreated;
use App\Notifications\JobRequestReceived;
use App\Notifications\NewJobRequest;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CreateJobRequestTest extends TestCase
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
            'contact_name' => 'Jane Citizen',
            'contact_email' => 'jane@example.com',
            'contact_phone' => '0412 345 678',
            'address_line' => '12 Example St',
            'suburb' => 'Fitzroy',
            'postcode' => '3065',
            'category' => 'plumbing',
            'title' => 'Leaking tap',
            'description' => 'The kitchen tap has been dripping constantly for a week.',
            'urgency' => 'within_a_week',
            ...$overrides,
        ];
    }

    public function test_admins_can_view_the_create_form()
    {
        $this->actingAs($this->admin)
            ->get(route('admin.job-requests.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/job-requests/create')
                ->has('options.statuses')
                ->has('options.categories'));
    }

    public function test_a_new_email_creates_an_account_and_links_the_request()
    {
        $otherAdmin = User::factory()->admin()->create();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.job-requests.store'), $this->validPayload([
                'contact_email' => ' Jane@Example.com ',
                'admin_notes' => 'Called in on the phone.',
            ]));

        $customer = User::query()->where('email', 'jane@example.com')->sole();
        $jobRequest = JobRequest::query()->sole();

        $response->assertRedirect(route('admin.job-requests.show', $jobRequest));

        $this->assertSame('Jane Citizen', $customer->name);
        $this->assertFalse($customer->hasVerifiedEmail());
        $this->assertFalse($customer->isAdmin());
        $this->assertSame($customer->id, $jobRequest->user_id);
        $this->assertNotNull($jobRequest->claimed_at);
        $this->assertSame('12 Example St', $jobRequest->address_line);
        $this->assertSame('Called in on the phone.', $jobRequest->admin_notes);
        $this->assertSame(JobRequestStatus::Submitted, $jobRequest->fresh()->status);

        $activity = $jobRequest->activities()->sole();
        $this->assertSame(ActivityEvent::Created, $activity->event);
        $this->assertSame($this->admin->id, $activity->user_id);

        Notification::assertSentTo($customer, CustomerAccountCreated::class);
        Notification::assertSentTo(
            $customer,
            JobRequestReceived::class,
            fn (JobRequestReceived $notification): bool => $notification->createdByAdmin,
        );
        Notification::assertSentTo($otherAdmin, NewJobRequest::class);
        Notification::assertNotSentTo($this->admin, NewJobRequest::class);
    }

    public function test_an_existing_email_attaches_the_request_without_a_new_account()
    {
        $customer = User::factory()->create(['email' => 'jane@example.com']);

        $this->actingAs($this->admin)
            ->post(route('admin.job-requests.store'), $this->validPayload(['contact_email' => 'JANE@example.com']))
            ->assertRedirect();

        $this->assertSame(2, User::query()->count());
        $this->assertSame($customer->id, JobRequest::query()->sole()->user_id);

        Notification::assertNotSentTo($customer, CustomerAccountCreated::class);
        Notification::assertSentTo($customer, JobRequestReceived::class);
    }

    public function test_the_status_can_be_set_when_creating()
    {
        $this->actingAs($this->admin)
            ->post(route('admin.job-requests.store'), $this->validPayload(['status' => 'reviewing']))
            ->assertRedirect();

        $this->assertSame(JobRequestStatus::Reviewing, JobRequest::query()->sole()->status);
    }

    public function test_invalid_requests_are_rejected_without_side_effects()
    {
        $this->actingAs($this->admin)
            ->post(route('admin.job-requests.store'), $this->validPayload(['postcode' => '2000', 'contact_email' => '']))
            ->assertSessionHasErrors(['postcode', 'contact_email']);

        $this->assertSame(0, JobRequest::query()->count());
        $this->assertSame(1, User::query()->count());
        Notification::assertNothingSent();
    }

    public function test_the_account_email_links_to_a_signed_setup_url()
    {
        $customer = User::factory()->unverified()->create();

        $mail = (new CustomerAccountCreated)->toMail($customer);

        $this->assertStringContainsString('/account/setup/'.$customer->id, $mail->actionUrl);
        $this->assertStringContainsString('signature=', $mail->actionUrl);
    }

    public function test_the_setup_link_verifies_the_email_and_redirects_to_set_a_password()
    {
        Event::fake([Verified::class]);
        $customer = User::factory()->unverified()->create();

        $response = $this->get((new CustomerAccountCreated)->setupUrl($customer));

        $this->assertTrue($customer->fresh()->hasVerifiedEmail());
        Event::assertDispatched(Verified::class);

        $location = $response->assertRedirect()->headers->get('Location');
        $this->assertStringStartsWith(url('/reset-password/'), $location);

        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $this->assertSame($customer->email, $query['email']);

        $token = basename((string) parse_url($location, PHP_URL_PATH));
        $this->assertTrue(Password::broker()->tokenExists($customer, $token));
    }

    public function test_the_setup_link_claims_other_requests_sent_from_that_email()
    {
        $customer = User::factory()->unverified()->create(['email' => 'jane@example.com']);
        $guestRequest = JobRequest::factory()->create(['contact_email' => 'jane@example.com']);

        $this->get((new CustomerAccountCreated)->setupUrl($customer))->assertRedirect();

        $this->assertSame($customer->id, $guestRequest->fresh()->user_id);
    }

    public function test_unsigned_or_expired_setup_links_are_rejected()
    {
        $customer = User::factory()->unverified()->create();

        $this->get(route('account.setup', $customer))->assertForbidden();

        $expired = URL::temporarySignedRoute('account.setup', now()->subMinute(), ['user' => $customer->id]);
        $this->get($expired)->assertForbidden();

        $this->assertFalse($customer->fresh()->hasVerifiedEmail());
    }

    public function test_admin_created_request_emails_use_admin_wording()
    {
        $mail = (new JobRequestReceived(
            JobCategory::Plumbing,
            JobUrgency::WithinAWeek,
            createdByAdmin: true,
        ))->toMail(new AnonymousNotifiable);

        $text = implode("\n", [...$mail->introLines, ...$mail->outroLines]);

        $this->assertStringContainsString("We've logged your plumbing request.", $text);
        $this->assertStringNotContainsString('create an account', $text);
        $this->assertStringNotContainsString("If you didn't send this request", $text);
    }
}
