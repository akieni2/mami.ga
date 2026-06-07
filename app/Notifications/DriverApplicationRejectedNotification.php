<?php

namespace App\Notifications;

use App\Models\DriverApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DriverApplicationRejectedNotification extends Notification
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
            ->subject('Candidature chauffeur refusée — MAMI.GA')
            ->greeting('Bonjour '.$this->application->first_name.',')
            ->line('Votre candidature chauffeur n\'a pas été retenue.')
            ->line('Motif : '.$this->application->rejection_reason)
            ->line('Vous pouvez soumettre une nouvelle candidature après correction.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'driver_application_rejected',
            'application_id' => $this->application->id,
            'status' => $this->application->status->value,
            'rejection_reason' => $this->application->rejection_reason,
            'message' => 'Votre candidature chauffeur a été refusée.',
        ];
    }
}
