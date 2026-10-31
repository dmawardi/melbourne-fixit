<?php

namespace App\Http\Controllers\JobRequests;

use App\Actions\JobRequests\ClaimJobRequests;
use App\Enums\ActivityEvent;
use App\Enums\JobCategory;
use App\Enums\JobUrgency;
use App\Http\Controllers\Concerns\ProvidesJobRequestOptions;
use App\Http\Controllers\Controller;
use App\Http\Requests\JobRequests\StoreJobRequestRequest;
use App\Http\Requests\JobRequests\UpdateJobRequestRequest;
use App\Http\Resources\JobRequestResource;
use App\Models\JobRequest;
use App\Models\User;
use App\Notifications\JobRequestReceived;
use App\Notifications\NewJobRequest;
use App\Support\StructuredData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class JobRequestController extends Controller
{
    use ProvidesJobRequestOptions;

    /**
     * Show the public job request form.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('job-requests/create', [
            'options' => $this->jobRequestOptions(),
            'defaults' => [
                'contact_name' => $request->user()?->name,
                'contact_email' => $request->user()?->email,
                // Preselected when arriving from a service link on the home page.
                'category' => JobCategory::tryFrom($request->string('category')->toString())?->value,
            ],
            'structuredData' => [
                StructuredData::breadcrumbs([
                    route('home') => 'Home',
                    route('job-requests.create') => 'Request a quote',
                ]),
            ],
        ]);
    }

    /**
     * Store a job request submitted from the public form.
     */
    public function store(StoreJobRequestRequest $request): RedirectResponse
    {
        $jobRequest = new JobRequest($request->safe()->except('website'));
        $jobRequest->submitted_ip = $request->ip();
        $jobRequest->submitted_user_agent = $request->userAgent();

        $user = $request->user();

        // Requests from logged-in, verified users are theirs straight away.
        if ($user instanceof User && $user->hasVerifiedEmail()) {
            $jobRequest->user_id = $user->id;
            $jobRequest->claimed_at = now();
            $jobRequest->save();
            $jobRequest->recordActivity(ActivityEvent::Created, by: $user);
            $this->sendNewRequestEmails($jobRequest);

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Request submitted. You can now add photos.')]);

            return to_route('job-requests.show', $jobRequest);
        }

        $jobRequest->save();
        $jobRequest->recordActivity(ActivityEvent::Created);
        $this->sendNewRequestEmails($jobRequest);

        return to_route('job-requests.submitted')->with([
            'submittedEmail' => $jobRequest->contact_email,
            'submittedEmergency' => $jobRequest->urgency === JobUrgency::Emergency,
        ]);
    }

    /**
     * Show the confirmation page for guest submissions.
     */
    public function submitted(Request $request): Response|RedirectResponse
    {
        $email = $request->session()->get('submittedEmail');

        if ($email === null) {
            return to_route('job-requests.create');
        }

        return Inertia::render('job-requests/submitted', [
            'email' => $email,
            'emergency' => $request->session()->get('submittedEmergency', false),
        ]);
    }

    /**
     * Show the user's job requests.
     */
    public function index(Request $request, ClaimJobRequests $claimJobRequests): Response
    {
        // Catches requests submitted as a guest while already logged in on another device.
        $claimJobRequests->handle($request->user());

        $jobRequests = $request->user()
            ->jobRequests()
            ->withCount(['files' => fn ($query) => $query->shared()])
            ->latest()
            ->paginate(10);

        return Inertia::render('job-requests/index', [
            'jobRequests' => JobRequestResource::collection($jobRequests),
        ]);
    }

    /**
     * Show a single job request.
     */
    public function show(Request $request, JobRequest $jobRequest): Response
    {
        Gate::authorize('view', $jobRequest);

        // Only load what the customer is allowed to see.
        $jobRequest->load([
            'files' => fn ($query) => $query->shared(),
            'messages' => fn ($query) => $query->shared(),
        ]);

        return Inertia::render('job-requests/show', [
            'jobRequest' => (new JobRequestResource($jobRequest))->resolve($request),
            'options' => $this->jobRequestOptions(),
            'maxPhotos' => JobRequest::MAX_PHOTOS,
        ]);
    }

    /**
     * Update a job request's details.
     */
    public function update(UpdateJobRequestRequest $request, JobRequest $jobRequest): RedirectResponse
    {
        $wasEmergency = $jobRequest->urgency === JobUrgency::Emergency;

        $jobRequest->updateWithActivity($request->validated(), $request->user());

        if (! $wasEmergency && $jobRequest->urgency === JobUrgency::Emergency) {
            Notification::send(User::query()->admins()->get(), new NewJobRequest($jobRequest));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Request updated.')]);

        return to_route('job-requests.show', $jobRequest);
    }

    /**
     * Delete a job request.
     */
    public function destroy(Request $request, JobRequest $jobRequest): RedirectResponse
    {
        Gate::authorize('delete', $jobRequest);

        $jobRequest->delete();
        $jobRequest->recordActivity(ActivityEvent::Deleted, by: $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Request deleted.')]);

        return to_route('job-requests.index');
    }

    /**
     * Email the admins and the customer about a new request.
     */
    private function sendNewRequestEmails(JobRequest $jobRequest): void
    {
        Notification::send(User::query()->admins()->get(), new NewJobRequest($jobRequest));

        Notification::route('mail', $jobRequest->contact_email)
            ->notify(new JobRequestReceived($jobRequest->category, $jobRequest->urgency));
    }
}
