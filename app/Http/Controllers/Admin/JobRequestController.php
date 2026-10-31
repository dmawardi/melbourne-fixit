<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActivityEvent;
use App\Enums\FileKind;
use App\Enums\JobCategory;
use App\Enums\JobRequestStatus;
use App\Enums\Visibility;
use App\Http\Controllers\Concerns\ProvidesJobRequestOptions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateJobRequestRequest;
use App\Http\Resources\Admin\JobRequestResource;
use App\Models\JobRequest;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobRequestController extends Controller
{
    use ProvidesJobRequestOptions;

    /**
     * The number of activity log entries shown on a request.
     */
    private const ACTIVITY_LIMIT = 100;

    /**
     * Show all job requests, most recently active first.
     */
    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->trim()->value(),
            'status' => JobRequestStatus::tryFrom($request->string('status')->value())?->value,
            'category' => JobCategory::tryFrom($request->string('category')->value())?->value,
            'claimed' => in_array($request->query('claimed'), ['yes', 'no'], true) ? $request->query('claimed') : null,
            'vendor' => in_array($request->query('vendor'), ['needs_vendor', 'assigned'], true) ? $request->query('vendor') : null,
        ];

        $jobRequests = JobRequest::query()
            ->with(['user', 'vendor', 'latestActivity.user'])
            ->withCount(['files', 'messages'])
            ->when($filters['search'], function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $term = '%'.$search.'%';

                    $query->where('title', 'like', $term)
                        ->orWhere('suburb', 'like', $term)
                        ->orWhere('postcode', 'like', $term)
                        ->orWhere('contact_name', 'like', $term)
                        ->orWhere('contact_email', 'like', $term);
                });
            })
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['category'], fn (Builder $query, string $category) => $query->where('category', $category))
            ->when($filters['claimed'] === 'yes', fn (Builder $query) => $query->whereNotNull('user_id'))
            ->when($filters['claimed'] === 'no', fn (Builder $query) => $query->whereNull('user_id'))
            ->when($filters['vendor'] === 'needs_vendor', fn (Builder $query) => $query->needsVendor())
            ->when($filters['vendor'] === 'assigned', fn (Builder $query) => $query->whereNotNull('vendor_id'))
            ->orderByDesc('last_activity_at')
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('admin/job-requests/index', [
            'jobRequests' => JobRequestResource::collection($jobRequests),
            'filters' => $filters,
            'options' => [
                'statuses' => JobRequestStatus::options(),
                'categories' => JobCategory::options(),
            ],
        ]);
    }

    /**
     * Show a job request with its files, messages and activity log.
     */
    public function show(Request $request, JobRequest $jobRequest): Response
    {
        $jobRequest->load([
            'user',
            'vendor',
            'files.uploader',
            'messages.author',
            'activities' => fn ($query) => $query->with('user')->limit(self::ACTIVITY_LIMIT),
        ]);

        return Inertia::render('admin/job-requests/show', [
            'jobRequest' => (new JobRequestResource($jobRequest))->resolve($request),
            'vendors' => Vendor::query()->orderBy('name')->get()->map(fn (Vendor $vendor): array => [
                'id' => $vendor->id,
                'name' => $vendor->name,
                'handles_category' => $vendor->canHandle($jobRequest->category),
            ]),
            'options' => [
                ...$this->jobRequestOptions(),
                'statuses' => JobRequestStatus::options(),
                'fileKinds' => FileKind::options(),
                'visibilities' => Visibility::options(),
            ],
        ]);
    }

    /**
     * Update a job request's details, status or notes.
     */
    public function update(UpdateJobRequestRequest $request, JobRequest $jobRequest): RedirectResponse
    {
        $jobRequest->updateWithActivity($request->validated(), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Request updated.')]);

        return to_route('admin.job-requests.show', $jobRequest);
    }

    /**
     * Delete a job request.
     */
    public function destroy(Request $request, JobRequest $jobRequest): RedirectResponse
    {
        $jobRequest->delete();
        $jobRequest->recordActivity(ActivityEvent::Deleted, by: $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Request deleted.')]);

        return to_route('admin.job-requests.index');
    }
}
