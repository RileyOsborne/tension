<?php

use App\Models\Game;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('components.layouts.app')] #[Title('Create Game')] class extends Component {
    public string $name = '';
    public int $playerCount = 2;

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'playerCount' => 'required|integer|min:2',
        ]);

        $game = Game::create([
            'name' => $this->name,
            'player_count' => $this->playerCount,
        ]);

        session()->flash('message', 'Game created! Now add players and categories.');
        $this->redirect(route('games.show', $game), navigate: true);
    }
}; ?>

<div>
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <a href="{{ route('games.index') }}" class="text-slate-400 hover:text-white transition">
                &larr; Back to Games
            </a>
            <h1 class="text-3xl font-bold mt-4">Create New Game</h1>
        </div>

        <form wire:submit="save" class="space-y-6">
            <div class="bg-slate-800 rounded-xl p-6 border border-slate-700">
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Game Name</label>
                        <input type="text"
                               wire:model="name"
                               placeholder="Friday Game Night"
                               class="w-full bg-slate-900 border border-slate-600 rounded-lg px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-red-500">
                        @error('name') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Number of Players</label>
                        <input type="number"
                               wire:model.live="playerCount"
                               min="2"
                               class="w-full bg-slate-900 border border-slate-600 rounded-lg px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-red-500">
                        @error('playerCount') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror

                        <p class="text-slate-400 mt-2">
                            This will create <span class="text-white font-semibold">{{ $playerCount * 2 }} rounds</span>
                            (2 rounds per player)
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-4">
                <a href="{{ route('games.index') }}"
                   class="px-6 py-3 bg-slate-700 hover:bg-slate-600 rounded-lg font-semibold transition">
                    Cancel
                </a>
                <button type="submit"
                        class="px-8 py-3 bg-red-600 hover:bg-red-700 rounded-lg font-semibold transition">
                    Create Game
                </button>
            </div>
        </form>
    </div>
</div>
