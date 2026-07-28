<?php

namespace App\Modules\JbLudo\Events;

use App\Modules\JbLudo\Models\GameMatch;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JbMatchUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public GameMatch $match,
        public string $eventName,
        public array $payload = [],
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('jb-match-'.$this->match->id)];
    }

    public function broadcastAs(): string
    {
        return $this->eventName;
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return array_merge([
            'match_id' => $this->match->id,
            'status' => $this->match->status->value,
            'turn_color' => $this->match->turn_color->value,
            'board_state' => $this->match->board_state,
            'move_count' => $this->match->move_count,
            'white_time_left' => $this->match->white_time_left,
            'black_time_left' => $this->match->black_time_left,
            'grace_until' => $this->match->grace_until?->toIso8601String(),
            'result' => $this->match->result?->value,
            'winner_id' => $this->match->winner_id,
        ], $this->payload);
    }
}
