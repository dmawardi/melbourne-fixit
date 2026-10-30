<?php

namespace App\Policies;

use App\Models\JobRequestFile;
use App\Models\User;

class JobRequestFilePolicy
{
    /**
     * Determine whether the user can update the file.
     *
     * Customers can only manage files they uploaded themselves, never files added by admins.
     */
    public function update(User $user, JobRequestFile $file): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $file->uploaded_by === $user->id
            && $user->can('update', $file->jobRequest);
    }

    /**
     * Determine whether the user can delete the file.
     */
    public function delete(User $user, JobRequestFile $file): bool
    {
        return $this->update($user, $file);
    }
}
