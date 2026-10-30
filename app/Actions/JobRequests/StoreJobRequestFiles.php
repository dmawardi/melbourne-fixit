<?php

namespace App\Actions\JobRequests;

use App\Enums\ActivityEvent;
use App\Enums\FileKind;
use App\Enums\Visibility;
use App\Models\JobRequest;
use App\Models\JobRequestFile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class StoreJobRequestFiles
{
    /**
     * Store uploaded files privately against a job request and log each upload.
     *
     * @param  array<int, UploadedFile>  $files
     * @return Collection<int, JobRequestFile>
     */
    public function handle(JobRequest $jobRequest, array $files, FileKind $kind, Visibility $visibility, User $uploader): Collection
    {
        $disk = config('filesystems.job_photos');
        $storedPaths = [];

        try {
            $stored = DB::transaction(function () use ($jobRequest, $files, $kind, $visibility, $uploader, $disk, &$storedPaths): Collection {
                $nextSortOrder = (int) $jobRequest->files()->max('sort_order') + 1;
                $stored = new Collection;

                foreach ($files as $file) {
                    $path = $file->storeAs(
                        "job-requests/{$jobRequest->public_id}",
                        Str::lower((string) Str::ulid()).'.'.$file->extension(),
                        ['disk' => $disk, 'visibility' => 'private'],
                    );

                    if ($path === false) {
                        throw new RuntimeException('Unable to store job request file.');
                    }

                    $storedPaths[] = $path;

                    $dimensions = str_starts_with((string) $file->getMimeType(), 'image/')
                        ? getimagesize($file->getRealPath())
                        : false;

                    $jobRequestFile = new JobRequestFile([
                        'kind' => $kind,
                        'visibility' => $visibility,
                        'disk' => $disk,
                        'path' => $path,
                        'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                        'width' => $dimensions ? $dimensions[0] : null,
                        'height' => $dimensions ? $dimensions[1] : null,
                        'sort_order' => $nextSortOrder++,
                        'uploaded_at' => now(),
                    ]);
                    $jobRequestFile->uploaded_by = $uploader->id;

                    $jobRequest->files()->save($jobRequestFile);
                    $stored->push($jobRequestFile);
                }

                return $stored;
            });
        } catch (Throwable $e) {
            // Don't leave orphaned files behind if any part of the upload fails.
            Storage::disk($disk)->delete($storedPaths);

            throw $e;
        }

        foreach ($stored as $jobRequestFile) {
            $jobRequest->recordActivity(ActivityEvent::FileUploaded, $jobRequestFile->activityProperties(), $uploader);
        }

        return $stored;
    }
}
