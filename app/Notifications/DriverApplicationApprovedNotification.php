<?php

namespace App\Notifications;

use App\Models\DriverApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DriverApplicationApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public DriverApplication $application,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Candidature chauffeur approuvée — MAMI.GA')
            ->greeting('Bonjour '.$this->application->first_name.',')
            ->line('Votre candidature chauffeur a été approuvée.')
            ->line('Vous pouvez maintenant vous connecter à l\'application chauffeur et activer votre disponibilité.')
            ->line('Bienvenue sur MAMI.GA !');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'driver_application_approved',
            'application_id' => $this->application->id,
            'status' => $this->application->status->value,
            'message' => 'Votre candidature chauffeur a été approuvée.',
        ];
    }
}
