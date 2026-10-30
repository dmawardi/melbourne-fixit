<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:make-admin {email : The email address of an existing user} {--revoke : Remove admin access instead}')]
#[Description('Grant or revoke admin access for a user')]
class MakeAdmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = mb_strtolower((string) $this->argument('email'));
        $user = User::query()->whereRaw('lower(email) = ?', [$email])->first();

        if ($user === null) {
            $this->components->error("No user found with the email [{$email}].");

            return self::FAILURE;
        }

        $revoke = (bool) $this->option('revoke');
        $user->forceFill(['is_admin' => ! $revoke])->save();

        $revoke
            ? $this->components->info("{$user->email} is no longer an admin.")
            : $this->components->info("{$user->email} is now an admin.");

        return self::SUCCESS;
    }
}
