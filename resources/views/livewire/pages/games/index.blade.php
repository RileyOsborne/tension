<?php

use App\Models\Game;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('components.layouts.app')] #[Title('Games')] class extends Component {
    public function with(): array
    {
        return [
            'games' => Game::query()
                ->withCount('players', 'rounds')
                ->latest()
                ->get(),
        ];
    }

    public function deleteGame(Game $game): void
    {
        $game->delete();
        session()->flash('message', 'Game deleted successfully.');
    }

    public function startGame(Game $game): void
    {
        if ($game->status !== 'ready') {
            return;
        }

        $game->update([
            'status' => 'playing',
            'current_round' => 1,
        ]);

        // Set first round to intro
        $game->rounds()->where('round_number', 1)->update(['status' => 'intro']);

        $this->redirect(route('games.control', $game), navigate: true);
    }
}; ?>

<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold">Games</h1>
            <a href="{{ route('games.create') }}"
               class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                + New Game
            </a>
        </div>

        @if (session('message'))
            <div class="bg-green-600/20 border border-green-500 text-green-400 px-4 py-3 rounded-lg mb-6">
                {{ session('message') }}
            </div>
        @endif

        @if($games->isEmpty())
            <div class="bg-slate-800 rounded-xl p-12 text-center">
                <p class="text-slate-400 text-lg mb-4">No games yet.</p>
                <a href="{{ route('games.create') }}" class="text-red-400 hover:text-red-300">
                    Create your first game
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($games as $game)
                    <div class="bg-slate-800 rounded-xl p-6 border border-slate-700 hover:border-slate-600 transition">
                        <div class="flex justify-between items-start mb-4">
                            <h2 class="text-xl font-semibold">{{ $game->name }}</h2>
                            <span class="text-sm px-2 py-1 rounded
                                @switch($game->status)
                                    @case('draft') bg-slate-600 text-slate-300 @break
                                    @case('ready') bg-blue-600/20 text-blue-400 @break
                                    @case('playing') bg-green-600/20 text-green-400 @break
                                    @case('completed') bg-purple-600/20 text-purple-400 @break
                                @endswitch
                            ">
                                {{ ucfirst($game->status) }}
                            </span>
                        </div>

                        <div class="text-slate-400 text-sm space-y-1 mb-4">
                            <p>{{ $game->players_count }} / {{ $game->player_count }} players</p>
                            <p>{{ $game->rounds_count }} / {{ $game->total_rounds }} rounds configured</p>
                            @if($game->status === 'playing')
                                <p>Currently on round {{ $game->current_round }}</p>
                            @endif
                        </div>

                        <div class="flex justify-between items-center pt-4 border-t border-slate-700">
                            <div class="flex gap-4">
                                @if($game->status === 'playing')
                                    <a href="{{ route('games.control', $game) }}"
                                       class="text-green-400 hover:text-green-300 font-medium">
                                        Continue
                                    </a>
                                @elseif($game->status === 'ready')
                                    <button wire:click="startGame('{{ $game->id }}')"
                                            class="text-green-400 hover:text-green-300 font-medium">
                                        Play
                                    </button>
                                    <a href="{{ route('games.show', $game) }}"
                                       class="text-blue-400 hover:text-blue-300 font-medium">
                                        Setup
                                    </a>
                                @elseif($game->status === 'completed')
                                    <a href="{{ route('games.show', $game) }}"
                                       class="text-purple-400 hover:text-purple-300 font-medium">
                                        View Results
                                    </a>
                                @else
                                    <a href="{{ route('games.show', $game) }}"
                                       class="text-blue-400 hover:text-blue-300 font-medium">
                                        Setup
                                    </a>
                                @endif
                            </div>
                            <button wire:click="deleteGame('{{ $game->id }}')"
                                    wire:confirm="Are you sure you want to delete this game?"
                                    class="text-red-400 hover:text-red-300 font-medium">
                                Delete
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
