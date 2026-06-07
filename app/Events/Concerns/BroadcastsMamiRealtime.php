<?php

namespace App\Events\Concerns;

use App\Models\Driver;
use App\Models\Ride;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;

trait BroadcastsMamiRealtime
{
    public function broadcastAs(): string
    {
        return class_basename(static::class);
    }

    protected function firebaseEnvelope(array $payload): array
    {
        return [
            'event' => $this->broadcastAs(),
            'payload' => $payload,
            'occurred_at' => now()->toIso8601String(),
        ];
    }

    protected function channelName(string $suffix): string
    {
        return config('mami.broadcast_prefix', 'mami').'.'.$suffix;
    }

    /**
     * Canaux legacy (public, admin / compat) + canaux Reverb privés Sprint 02.
     *
     * @return array<int, Channel|PrivateChannel>
     */
    protected function rideBroadcastChannels(Ride $ride): array
    {
        $channels = [
            new Channel($this->channelName('rides.'.$ride->id)),
            new PrivateChannel('ride-'.$ride->id),
            new PrivateChannel('user-'.$ride->client_id),
        ];

        if ($ride->driver_id !== null) {
            $channels[] = new Channel($this->channelName('drivers.'.$ride->driver_id));
            $channels[] = new PrivateChannel('driver-'.$ride->driver_id);
        }

        return $channels;
    }

    /**
     * @return array<int, Channel|PrivateChannel>
     */
    protected function driverBroadcastChannels(Driver $driver, ?Ride $ride = null): array
    {
        $channels = [
            new Channel($this->channelName('drivers.'.$driver->id)),
            new PrivateChannel('driver-'.$driver->id),
        ];

        if ($ride !== null) {
            $channels = array_merge($channels, $this->rideBroadcastChannels($ride));
        }

        return $channels;
    }
}
