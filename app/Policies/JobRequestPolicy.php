<?php

namespace App\Policies;

use App\Models\JobRequest;
use App\Models\User;

class JobRequestPolicy
{
    /**
     * Determine whether the user can view the job request.
     */
    public function view(User $user, JobRequest $jobRequest): bool
    {
        return $user->id === $jobRequest->user_id;
    }

    /**
     * Determine whether the user can update the job request or manage its photos.
     */
    public function update(User $user, JobRequest $jobRequest): bool
    {
        return $this->view($user, $jobRequest) && $jobRequest->status->isEditable();
    }

    /**
     * Determine whether the user can delete the job request.
     */
    public function delete(User $user, JobRequest $jobRequest): bool
    {
        return $this->update($user, $jobRequest);
    }
}
