<?php

namespace App\Listeners;

use App\Actions\JobRequests\ClaimJobRequests;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Verified;

class ClaimJobRequestsForUser
{
    /**
     * Create the event listener.
     */
    public function __construct(private ClaimJobRequests $claimJobRequests)
    {
        //
    }

    /**
     * Claim requests when a user with a verified email logs in.
     */
    public function handleLogin(Login $event): void
    {
        if ($event->user instanceof User) {
            $this->claimJobRequests->handle($event->user);
        }
    }

    /**
     * Claim requests as soon as a user verifies their email.
     */
    public function handleVerified(Verified $event): void
    {
        if ($event->user instanceof User) {
            $this->claimJobRequests->handle($event->user);
        }
    }
}
