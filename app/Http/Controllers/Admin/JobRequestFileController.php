<?php

namespace App\Http\Controllers\Admin;

use App\Actions\JobRequests\StoreJobRequestFiles;
use App\Enums\ActivityEvent;
use App\Enums\FileKind;
use App\Enums\Visibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreJobRequestFileRequest;
use App\Http\Requests\Admin\UpdateJobRequestFileRequest;
use App\Models\JobRequest;
use App\Models\JobRequestFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JobRequestFileController extends Controller
{
    /**
     * Upload files to a job request.
     */
    public function store(StoreJobRequestFileRequest $request, JobRequest $jobRequest, StoreJobRequestFiles $storeJobRequestFiles): RedirectResponse
    {
        $storeJobRequestFiles->handle(
            $jobRequest,
            $request->file('files'),
            $request->enum('kind', FileKind::class),
            $request->enum('visibility', Visibility::class),
            $request->user(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Files uploaded.')]);

        return to_route('admin.job-requests.show', $jobRequest);
    }

    /**
     * Update a file's visibility, kind or caption.
     */
    public function update(UpdateJobRequestFileRequest $request, JobRequest $jobRequest, JobRequestFile $file): RedirectResponse
    {
        $file->updateWithActivity($request->validated(), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('File updated.')]);

        return to_route('admin.job-requests.show', $jobRequest);
    }

    /**
     * Delete a file from a job request.
     */
    public function destroy(Request $request, JobRequest $jobRequest, JobRequestFile $file): RedirectResponse
    {
        $file->delete();
        $jobRequest->recordActivity(ActivityEvent::FileDeleted, $file->activityProperties(), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('File deleted.')]);

        return to_route('admin.job-requests.show', $jobRequest);
    }
}
