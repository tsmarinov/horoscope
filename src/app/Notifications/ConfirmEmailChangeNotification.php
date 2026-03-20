<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class ConfirmEmailChangeNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $newEmail) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute(
            'email.change.confirm',
            now()->addDays(3),
            ['id' => $notifiable->getKey()]
        );

        return (new MailMessage)
            ->subject('Confirm your new email address')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('You requested to change your email address to: **' . $this->newEmail . '**')
            ->action('Confirm New Email', $url)
            ->line('This link expires in 3 days. If you did not request this change, you can ignore this email — your current email will remain unchanged.');
    }
}
