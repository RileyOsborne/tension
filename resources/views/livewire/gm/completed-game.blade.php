<?php

use App\Models\Game;
use Livewire\Volt\Component;

new class extends Component {
    public Game $game;

    public function with(): array
    {
        return [
            'players' => $this->game->players->filter(fn($p) => $p->isActive())->sortByDesc('total_score')->values(),
        ];
    }
}; ?>

<div class="bg-slate-800 rounded-xl p-8 text-center">
    <h2 class="text-4xl font-bold mb-4">Game Complete!</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-3xl mx-auto">
        @foreach($players as $index => $player)
            <div class="bg-slate-900 rounded-xl p-4 {{ $index === 0 ? 'ring-2 ring-yellow-500' : '' }}">
                <div class="text-lg font-bold" style="color: {{ $player->color }}">
                    @if($index === 0) 👑 @endif
                    {{ $player->name }}
                </div>
                <div class="text-3xl font-bold mt-2">{{ $player->total_score }}</div>
            </div>
        @endforeach
    </div>
</div>
