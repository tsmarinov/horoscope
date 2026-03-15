<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class ConfirmEmailNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = URL::temporarySignedRoute(
            'email.verify',
            now()->addDays(7),
            ['id' => $notifiable->id, 'hash' => sha1($notifiable->email)]
        );

        return (new MailMessage)
            ->subject('Confirm your Stellar Omens email')
            ->greeting('Welcome to Stellar Omens!')
            ->line('Please confirm your email address to complete your registration.')
            ->action('Confirm Email', $url)
            ->line('This link expires in 7 days.')
            ->line('If you did not create an account, no further action is required.');
    }
}
