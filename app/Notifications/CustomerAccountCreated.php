<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Welcomes a customer whose account an admin created while logging a request for them.
 */
class CustomerAccountCreated extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The number of days the set-password link stays valid.
     */
    public const LINK_EXPIRY_DAYS = 7;

    /**
     * Get the notification's delivery channels.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(User $notifiable): MailMessage
    {
        $appName = config('app.name');
        $phone = config('business.phone');

        return (new MailMessage)
            ->subject("Your {$appName} account is ready")
            ->greeting("Hello {$notifiable->name},")
            ->line("We've set up a {$appName} account for you so you can follow your request, add photos and see updates from us.")
            ->line('Set a password to get started.')
            ->action('Set your password', $this->setupUrl($notifiable))
            ->line('This link expires in '.self::LINK_EXPIRY_DAYS.' days. After that, use "Forgot password" on the log in page.')
            ->line("If you weren't expecting this, please contact us".($phone ? " on {$phone}." : '.'));
    }

    /**
     * Get the signed link that verifies the customer's email and lets them set a password.
     */
    public function setupUrl(User $notifiable): string
    {
        return URL::temporarySignedRoute(
            'account.setup',
            now()->addDays(self::LINK_EXPIRY_DAYS),
            ['user' => $notifiable->id],
        );
    }
}
