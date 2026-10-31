<?php

namespace App\Notifications;

use App\Enums\JobCategory;
use App\Enums\JobUrgency;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Confirms a request to the customer. The form accepts any email address, so this deliberately
 * contains nothing the sender typed, which stops it being used to message a stranger.
 */
class JobRequestReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public JobCategory $category,
        public JobUrgency $urgency,
    ) {}

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
    public function toMail(object $notifiable): MailMessage
    {
        $emergency = $this->urgency === JobUrgency::Emergency;
        $phone = config('business.phone');

        $mail = (new MailMessage)
            ->subject($emergency ? "We've received your emergency request" : "We've received your request")
            ->greeting('Hello,')
            ->line("Thanks, we've received your ".mb_strtolower($this->category->label()).' request.');

        if ($emergency) {
            $mail->line(config('business.emergency_response'))
                ->line("If there's immediate danger, such as a gas leak, exposed wiring or flooding, call 000 first.");
        } else {
            $mail->line("We'll review the details and get back to you with a quote.");
        }

        if ($phone) {
            $mail->line("Need us in a hurry? Call {$phone}.");
        }

        return $mail
            ->line('To add photos and follow updates, log in or create an account with this email address.')
            ->action('Log in', route('login'))
            ->line("If you didn't send this request, you can ignore this email.");
    }
}
