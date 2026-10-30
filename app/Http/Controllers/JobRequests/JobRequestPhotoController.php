<?php

namespace App\Http\Controllers\JobRequests;

use App\Http\Controllers\Controller;
use App\Http\Requests\JobRequests\StoreJobRequestPhotoRequest;
use App\Http\Requests\JobRequests\UpdateJobRequestPhotoRequest;
use App\Models\JobRequest;
use App\Models\JobRequestPhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use RuntimeException;
use Throwable;

class JobRequestPhotoController extends Controller
{
    /**
     * Upload photos to a job request.
     */
    public function store(StoreJobRequestPhotoRequest $request, JobRequest $jobRequest): RedirectResponse
    {
        $disk = config('filesystems.job_photos');
        $storedPaths = [];

        try {
            DB::transaction(function () use ($request, $jobRequest, $disk, &$storedPaths): void {
                $nextSortOrder = (int) $jobRequest->photos()->max('sort_order') + 1;

                /** @var UploadedFile $file */
                foreach ($request->file('photos') as $file) {
                    $path = $file->storeAs(
                        "job-requests/{$jobRequest->public_id}",
                        Str::lower((string) Str::ulid()).'.'.$file->extension(),
                        ['disk' => $disk, 'visibility' => 'private'],
                    );

                    if ($path === false) {
                        throw new RuntimeException('Unable to store job request photo.');
                    }

                    $storedPaths[] = $path;

                    [$width, $height] = getimagesize($file->getRealPath()) ?: [null, null];

                    $photo = new JobRequestPhoto([
                        'disk' => $disk,
                        'path' => $path,
                        'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                        'width' => $width,
                        'height' => $height,
                        'sort_order' => $nextSortOrder++,
                        'uploaded_at' => now(),
                    ]);
                    $photo->uploaded_by = $request->user()->id;

                    $jobRequest->photos()->save($photo);
                }
            });
        } catch (Throwable $e) {
            // Don't leave orphaned files behind if any part of the upload fails.
            Storage::disk($disk)->delete($storedPaths);

            throw $e;
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Photos uploaded.')]);

        return to_route('job-requests.show', $jobRequest);
    }

    /**
     * Update a photo's caption or position.
     */
    public function update(UpdateJobRequestPhotoRequest $request, JobRequest $jobRequest, JobRequestPhoto $photo): RedirectResponse
    {
        $photo->update($request->validated());

        return to_route('job-requests.show', $jobRequest);
    }

    /**
     * Remove a photo from a job request.
     */
    public function destroy(JobRequest $jobRequest, JobRequestPhoto $photo): RedirectResponse
    {
        Gate::authorize('update', $jobRequest);

        $photo->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Photo removed.')]);

        return to_route('job-requests.show', $jobRequest);
    }
}
