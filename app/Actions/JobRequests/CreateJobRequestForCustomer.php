<?php

namespace App\Actions\JobRequests;

use App\Enums\ActivityEvent;
use App\Models\JobRequest;
use App\Models\User;
use App\Notifications\CustomerAccountCreated;
use App\Notifications\JobRequestReceived;
use App\Notifications\NewJobRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class CreateJobRequestForCustomer
{
    /**
     * Create a request on a customer's behalf, linked to their account.
     *
     * If no account exists for the contact email, one is created and the customer is emailed a
     * link to set their password.
     *
     * @param  array<string, mixed>  $attributes  Validated attributes.
     * @return array{0: JobRequest, 1: bool} The request, and whether a new account was created.
     */
    public function handle(array $attributes, User $admin): array
    {
        $email = mb_strtolower(trim($attributes['contact_email']));

        [$jobRequest, $customer, $accountCreated] = DB::transaction(function () use ($attributes, $email, $admin): array {
            $customer = User::query()->where('email', $email)->first();
            $accountCreated = false;

            if ($customer === null) {
                // The random password is never shared; the customer sets their own from the welcome email.
                $customer = User::create([
                    'name' => $attributes['contact_name'],
                    'email' => $email,
                    'password' => Str::password(32),
                ]);
                $accountCreated = true;
            }

            $jobRequest = new JobRequest($attributes);
            $jobRequest->forceFill([
                'user_id' => $customer->id,
                'claimed_at' => now(),
            ])->save();
            $jobRequest->recordActivity(ActivityEvent::Created, by: $admin);

            return [$jobRequest, $customer, $accountCreated];
        });

        Notification::send(
            User::query()->admins()->whereKeyNot($admin->id)->get(),
            new NewJobRequest($jobRequest),
        );

        if ($accountCreated) {
            $customer->notify(new CustomerAccountCreated);
        }

        $customer->notify(new JobRequestReceived($jobRequest->category, $jobRequest->urgency, createdByAdmin: true));

        return [$jobRequest, $accountCreated];
    }
}
