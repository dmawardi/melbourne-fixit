<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActivityEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreJobRequestMessageRequest;
use App\Models\JobRequest;
use App\Models\JobRequestMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JobRequestMessageController extends Controller
{
    /**
     * Post a timestamped update on a job request.
     */
    public function store(StoreJobRequestMessageRequest $request, JobRequest $jobRequest): RedirectResponse
    {
        $message = new JobRequestMessage($request->validated());
        $message->user_id = $request->user()->id;
        $jobRequest->messages()->save($message);

        $jobRequest->recordActivity(ActivityEvent::MessagePosted, [
            'message_id' => $message->id,
            'excerpt' => $message->excerpt(),
            'visibility' => $message->visibility->value,
        ], $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Update posted.')]);

        return to_route('admin.job-requests.show', $jobRequest);
    }

    /**
     * Delete an update from a job request.
     */
    public function destroy(Request $request, JobRequest $jobRequest, JobRequestMessage $message): RedirectResponse
    {
        $message->delete();

        $jobRequest->recordActivity(ActivityEvent::MessageDeleted, [
            'message_id' => $message->id,
            'excerpt' => $message->excerpt(),
            'visibility' => $message->visibility->value,
        ], $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Update deleted.')]);

        return to_route('admin.job-requests.show', $jobRequest);
    }
}
