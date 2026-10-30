<?php

namespace App\Http\Controllers\JobRequests;

use App\Actions\JobRequests\StoreJobRequestFiles;
use App\Enums\ActivityEvent;
use App\Enums\FileKind;
use App\Enums\Visibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\JobRequests\StoreJobRequestPhotoRequest;
use App\Http\Requests\JobRequests\UpdateJobRequestPhotoRequest;
use App\Models\JobRequest;
use App\Models\JobRequestFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class JobRequestFileController extends Controller
{
    /**
     * Upload the customer's photos to a job request.
     */
    public function store(StoreJobRequestPhotoRequest $request, JobRequest $jobRequest, StoreJobRequestFiles $storeJobRequestFiles): RedirectResponse
    {
        $storeJobRequestFiles->handle(
            $jobRequest,
            $request->file('photos'),
            FileKind::Photo,
            Visibility::Customer,
            $request->user(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Photos uploaded.')]);

        return to_route('job-requests.show', $jobRequest);
    }

    /**
     * Update a photo's caption or position.
     */
    public function update(UpdateJobRequestPhotoRequest $request, JobRequest $jobRequest, JobRequestFile $file): RedirectResponse
    {
        $file->updateWithActivity($request->validated(), $request->user());

        return to_route('job-requests.show', $jobRequest);
    }

    /**
     * Remove one of the customer's photos from a job request.
     */
    public function destroy(Request $request, JobRequest $jobRequest, JobRequestFile $file): RedirectResponse
    {
        // Admin-only files must not be revealed, even by a different error code.
        abort_unless($file->visibility === Visibility::Customer, 404);
        Gate::authorize('delete', $file);

        $file->delete();
        $jobRequest->recordActivity(ActivityEvent::FileDeleted, $file->activityProperties(), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Photo removed.')]);

        return to_route('job-requests.show', $jobRequest);
    }
}
