<?php

namespace App\Events\Concerns;

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
}
