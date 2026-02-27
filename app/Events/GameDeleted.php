<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameDeleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $gameId
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('game.' . $this->gameId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'game.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'gameId' => $this->gameId,
            'deleted' => true,
        ];
    }
}
