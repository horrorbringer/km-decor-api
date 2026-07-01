<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\URL;

class VerifyEmailApi extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject(Lang::get('Verify Email Address'))
            ->line(Lang::get('Please click the button below to verify your email address.'))
            ->action(Lang::get('Verify Email Address'), $verificationUrl)
            ->line(Lang::get('If you did not create an account, no further action is required.'));
    }

    protected function verificationUrl($notifiable): string
    {
        $signedUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['user' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())],
            false
        );

        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
        $signedParts = parse_url($signedUrl);
        $query = isset($signedParts['query']) ? '?'.$signedParts['query'] : '';

        return "{$frontendUrl}/verify-email/{$notifiable->getKey()}/".sha1($notifiable->getEmailForVerification()).$query;
    }
}
