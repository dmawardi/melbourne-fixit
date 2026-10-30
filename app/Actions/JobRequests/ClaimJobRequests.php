<?php

namespace App\Actions\JobRequests;

use App\Models\JobRequest;
use App\Models\User;

class ClaimJobRequests
{
    /**
     * Link unclaimed job requests submitted with the user's email address to their account.
     *
     * Only verified emails can claim requests, since anyone can type any address into the public form.
     */
    public function handle(User $user): int
    {
        if (! $user->hasVerifiedEmail()) {
            return 0;
        }

        return JobRequest::query()
            ->unclaimed()
            ->where('contact_email', mb_strtolower($user->email))
            ->update([
                'user_id' => $user->id,
                'claimed_at' => now(),
            ]);
    }
}
