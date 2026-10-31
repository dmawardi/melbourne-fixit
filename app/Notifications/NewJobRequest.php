<?php

namespace App\Notifications;

use App\Enums\JobUrgency;
use App\Models\JobRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Tells admins about a new request, or one a customer has changed to an emergency.
 */
class NewJobRequest extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Skip the email if the request is deleted before it's sent.
     */
    public bool $deleteWhenMissingModels = true;

    public function __construct(public JobRequest $jobRequest) {}

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
        $jobRequest = $this->jobRequest;
        $emergency = $jobRequest->urgency === JobUrgency::Emergency;

        $mail = (new MailMessage)
            ->subject(($emergency ? 'EMERGENCY: ' : 'New request: ')."{$jobRequest->title} in {$jobRequest->suburb}")
            ->replyTo($jobRequest->contact_email, $jobRequest->contact_name)
            ->greeting($emergency ? 'Emergency request' : 'New request');

        if ($emergency) {
            $mail->line('**This request is marked as an emergency. Please respond as soon as possible.**');
        }

        return $mail
            ->line("**{$jobRequest->title}**")
            ->line("{$jobRequest->category->label()} in {$jobRequest->suburb} {$jobRequest->postcode}")
            ->line("How soon: {$jobRequest->urgency->label()}")
            ->line(Str::limit($jobRequest->description, 1000))
            ->line("Customer: {$jobRequest->contact_name}")
            ->line('Phone: '.($jobRequest->contact_phone ?? 'Not given'))
            ->line("Email: {$jobRequest->contact_email}")
            ->action('View request', route('admin.job-requests.show', $jobRequest));
    }
}
