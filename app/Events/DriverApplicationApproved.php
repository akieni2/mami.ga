<?php

namespace App\Events;

use App\Events\Concerns\BroadcastsMamiRealtime;
use App\Models\DriverApplication;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverApplicationApproved implements ShouldBroadcastNow
{
    use BroadcastsMamiRealtime, Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public DriverApplication $application) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        if ($this->application->user_id === null) {
            return [];
        }

        return [new PrivateChannel('user-'.$this->application->user_id)];
    }

    public function broadcastWith(): array
    {
        return $this->firebaseEnvelope([
            'application_id' => $this->application->id,
            'status' => $this->application->status->value,
            'user_id' => $this->application->user_id,
        ]);
    }
}
