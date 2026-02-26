<?php

namespace App\Events;

use App\Models\Game;
use App\Models\Player;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlayerJoinedGame implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Game $game,
        public Player $player
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('game.' . $this->game->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'player.joined';
    }

    public function broadcastWith(): array
    {
        return [
            'player' => [
                'id' => $this->player->id,
                'name' => $this->player->name,
                'color' => $this->player->color,
            ],
        ];
    }
}
