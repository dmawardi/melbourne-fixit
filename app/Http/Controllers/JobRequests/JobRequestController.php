<?php

namespace App\Http\Controllers\JobRequests;

use App\Actions\JobRequests\ClaimJobRequests;
use App\Enums\BudgetRange;
use App\Enums\JobCategory;
use App\Enums\JobUrgency;
use App\Enums\PropertyType;
use App\Http\Controllers\Controller;
use App\Http\Requests\JobRequests\StoreJobRequestRequest;
use App\Http\Requests\JobRequests\UpdateJobRequestRequest;
use App\Http\Resources\JobRequestResource;
use App\Models\JobRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class JobRequestController extends Controller
{
    /**
     * Show the public job request form.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('job-requests/create', [
            'options' => $this->formOptions(),
            'defaults' => [
                'contact_name' => $request->user()?->name,
                'contact_email' => $request->user()?->email,
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

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Request submitted. You can now add photos.')]);

            return to_route('job-requests.show', $jobRequest);
        }

        $jobRequest->save();

        return to_route('job-requests.submitted')->with('submittedEmail', $jobRequest->contact_email);
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
            ->withCount('photos')
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

        $jobRequest->load('photos');

        return Inertia::render('job-requests/show', [
            'jobRequest' => (new JobRequestResource($jobRequest))->resolve($request),
            'options' => $this->formOptions(),
            'maxPhotos' => JobRequest::MAX_PHOTOS,
        ]);
    }

    /**
     * Update a job request's details.
     */
    public function update(UpdateJobRequestRequest $request, JobRequest $jobRequest): RedirectResponse
    {
        $jobRequest->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Request updated.')]);

        return to_route('job-requests.show', $jobRequest);
    }

    /**
     * Delete a job request.
     */
    public function destroy(JobRequest $jobRequest): RedirectResponse
    {
        Gate::authorize('delete', $jobRequest);

        $jobRequest->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Request deleted.')]);

        return to_route('job-requests.index');
    }

    /**
     * Get the select options for the job request form.
     *
     * @return array<string, list<array{value: string, label: string}>>
     */
    private function formOptions(): array
    {
        return [
            'categories' => JobCategory::options(),
            'urgencies' => JobUrgency::options(),
            'budgetRanges' => BudgetRange::options(),
            'propertyTypes' => PropertyType::options(),
        ];
    }
}
