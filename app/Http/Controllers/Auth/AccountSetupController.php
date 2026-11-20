<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;

class AccountSetupController extends Controller
{
    /**
     * Handle the set-password link from an admin-created account's welcome email.
     *
     * The signed link proves the customer received the email, so their address is verified, then
     * they're sent to the password reset form with a fresh token.
     */
    public function __invoke(User $user): RedirectResponse
    {
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();

            event(new Verified($user));
        }

        $token = Password::broker(config('fortify.passwords'))->createToken($user);

        return redirect()->route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]);
    }
}
