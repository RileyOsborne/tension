<?php

use App\Models\Game;
use App\Models\Player;
use App\Models\Category;
use App\Models\Answer;
use App\Models\Round;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('components.layouts.app')] #[Title('Game Setup')] class extends Component {
    public Game $game;

    public string $newPlayerName = '';
    public string $newPlayerColor = '#3B82F6';

    public array $roundCategories = [];

    // Category modal
    public bool $showCategoryModal = false;
    public string $categoryTitle = '';
    public string $categoryDescription = '';
    public array $categoryAnswers = [];
    public array $categoryAnswerStats = [];
    public string $bulkAnswers = '';

    public function mount(Game $game): void
    {
        $this->game = $game->load(['players', 'rounds.category']);

        // Initialize round categories from existing rounds
        foreach ($game->rounds as $round) {
            $this->roundCategories[$round->round_number] = $round->category_id;
        }

        // Initialize empty category answers
        $this->resetCategoryForm();
    }

    public function with(): array
    {
        return [
            'categories' => Category::whereHas('answers', function ($q) {
                $q->where('position', '<=', 10);
            }, '>=', 10)->get(),
        ];
    }

    public function openCategoryModal(): void
    {
        $this->resetCategoryForm();
        $this->showCategoryModal = true;
    }

    public function closeCategoryModal(): void
    {
        $this->showCategoryModal = false;
    }

    public function resetCategoryForm(): void
    {
        $this->categoryTitle = '';
        $this->categoryDescription = '';
        $this->categoryAnswers = [];
        $this->categoryAnswerStats = [];
        $this->bulkAnswers = '';
        for ($i = 1; $i <= 15; $i++) {
            $this->categoryAnswers[$i] = '';
            $this->categoryAnswerStats[$i] = '';
        }
    }

    public function parseBulkAnswers(): void
    {
        $lines = array_filter(array_map('trim', explode("\n", $this->bulkAnswers)));
        $position = 1;

        foreach ($lines as $line) {
            if ($position > 15) break;
            // Remove common list prefixes like "1.", "1)", "- ", etc.
            $clean = preg_replace('/^[\d]+[\.\)\-]\s*/', '', $line);
            $clean = preg_replace('/^[\-\*\•]\s*/', '', $clean);

            // Parse stat from format "Answer: stat" - uses last colon so "Avengers: Endgame: $2.8B" works
            if (preg_match('/^(.+)\s*[:]\s*([^:]+)$/', trim($clean), $matches)) {
                $this->categoryAnswers[$position] = trim($matches[1]);
                $this->categoryAnswerStats[$position] = trim($matches[2]);
            } else {
                $this->categoryAnswers[$position] = trim($clean);
                $this->categoryAnswerStats[$position] = '';
            }
            $position++;
        }

        $this->bulkAnswers = '';
    }

    public function moveAnswerUp(int $position): void
    {
        if ($position <= 1) return;

        $temp = $this->categoryAnswers[$position - 1];
        $this->categoryAnswers[$position - 1] = $this->categoryAnswers[$position];
        $this->categoryAnswers[$position] = $temp;

        $tempStat = $this->categoryAnswerStats[$position - 1];
        $this->categoryAnswerStats[$position - 1] = $this->categoryAnswerStats[$position];
        $this->categoryAnswerStats[$position] = $tempStat;
    }

    public function moveAnswerDown(int $position): void
    {
        if ($position >= 15) return;

        $temp = $this->categoryAnswers[$position + 1];
        $this->categoryAnswers[$position + 1] = $this->categoryAnswers[$position];
        $this->categoryAnswers[$position] = $temp;

        $tempStat = $this->categoryAnswerStats[$position + 1];
        $this->categoryAnswerStats[$position + 1] = $this->categoryAnswerStats[$position];
        $this->categoryAnswerStats[$position] = $tempStat;
    }

    public function saveCategory(): void
    {
        $this->validate([
            'categoryTitle' => 'required|string|max:255',
            'categoryDescription' => 'nullable|string',
            'categoryAnswers.1' => 'required|string|max:255',
            'categoryAnswers.2' => 'required|string|max:255',
            'categoryAnswers.3' => 'required|string|max:255',
            'categoryAnswers.4' => 'required|string|max:255',
            'categoryAnswers.5' => 'required|string|max:255',
            'categoryAnswers.6' => 'required|string|max:255',
            'categoryAnswers.7' => 'required|string|max:255',
            'categoryAnswers.8' => 'required|string|max:255',
            'categoryAnswers.9' => 'required|string|max:255',
            'categoryAnswers.10' => 'required|string|max:255',
        ], [
            'categoryAnswers.*.required' => 'Answers 1-10 are required.',
        ]);

        $category = Category::create([
            'title' => $this->categoryTitle,
            'description' => $this->categoryDescription ?: null,
        ]);

        foreach ($this->categoryAnswers as $position => $text) {
            if (!empty(trim($text))) {
                Answer::create([
                    'category_id' => $category->id,
                    'text' => trim($text),
                    'stat' => !empty(trim($this->categoryAnswerStats[$position] ?? '')) ? trim($this->categoryAnswerStats[$position]) : null,
                    'position' => $position,
                ]);
            }
        }

        $this->showCategoryModal = false;
        $this->resetCategoryForm();
    }

    public function addPlayer(): void
    {
        $this->validate([
            'newPlayerName' => 'required|string|max:255',
            'newPlayerColor' => 'required|string',
        ]);

        if ($this->game->players()->count() >= $this->game->player_count) {
            session()->flash('error', 'Maximum players reached.');
            return;
        }

        Player::create([
            'game_id' => $this->game->id,
            'name' => $this->newPlayerName,
            'color' => $this->newPlayerColor,
        ]);

        $this->newPlayerName = '';
        $this->game->refresh();
        $this->checkReady();
    }

    public function removePlayer(Player $player): void
    {
        $player->delete();
        $this->game->refresh();
        $this->checkReady();
    }

    public function setRoundCategory(int $roundNumber, ?string $categoryId): void
    {
        if (empty($categoryId)) {
            // Remove round if exists
            $this->game->rounds()->where('round_number', $roundNumber)->delete();
            unset($this->roundCategories[$roundNumber]);
        } else {
            // Create or update round
            Round::updateOrCreate(
                ['game_id' => $this->game->id, 'round_number' => $roundNumber],
                ['category_id' => $categoryId]
            );
            $this->roundCategories[$roundNumber] = $categoryId;
        }

        $this->game->refresh();
        $this->checkReady();
    }

    public function checkReady(): void
    {
        $playersReady = $this->game->players()->count() === $this->game->player_count;
        $roundsReady = $this->game->rounds()->count() === $this->game->total_rounds;

        if ($playersReady && $roundsReady && $this->game->status === 'draft') {
            $this->game->update(['status' => 'ready']);
        } elseif ((!$playersReady || !$roundsReady) && $this->game->status === 'ready') {
            $this->game->update(['status' => 'draft']);
        }
    }

    public function startGame(): void
    {
        if ($this->game->status !== 'ready') {
            return;
        }

        $this->game->update([
            'status' => 'playing',
            'current_round' => 1,
        ]);

        // Set first round to intro
        $this->game->rounds()->where('round_number', 1)->update(['status' => 'intro']);

        $this->redirect(route('games.control', $this->game), navigate: true);
    }

    public function resetGame(): void
    {
        // Reset player scores and double usage
        $this->game->players()->update([
            'total_score' => 0,
            'double_used' => false,
        ]);

        // Delete all player answers
        foreach ($this->game->rounds as $round) {
            $round->playerAnswers()->delete();
        }

        // Reset all rounds to pending
        $this->game->rounds()->update([
            'status' => 'pending',
            'current_slide' => 0,
        ]);

        // Reset game status
        $this->game->update([
            'status' => 'ready',
            'current_round' => 1,
        ]);

        $this->game->refresh();

        // Broadcast reset state to presentation
        $this->dispatch('game-reset', gameId: $this->game->id);

        session()->flash('message', 'Game has been reset! All scores cleared.');
    }
}; ?>

<div>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <a href="{{ route('games.index') }}" class="text-slate-400 hover:text-white transition">
                &larr; Back to Games
            </a>
            <div class="flex justify-between items-center mt-4">
                <h1 class="text-3xl font-bold">{{ $game->name }}</h1>
                <span class="text-sm px-3 py-1 rounded
                    @switch($game->status)
                        @case('draft') bg-slate-600 text-slate-300 @break
                        @case('ready') bg-green-600/20 text-green-400 @break
                        @case('playing') bg-blue-600/20 text-blue-400 @break
                    @endswitch
                ">
                    {{ ucfirst($game->status) }}
                </span>
            </div>
        </div>

        @if (session('message'))
            <div class="bg-green-600/20 border border-green-500 text-green-400 px-4 py-3 rounded-lg mb-6">
                {{ session('message') }}
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-600/20 border border-red-500 text-red-400 px-4 py-3 rounded-lg mb-6">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid lg:grid-cols-2 gap-8">
            <!-- Players Section -->
            <div class="bg-slate-800 rounded-xl p-6 border border-slate-700">
                <h2 class="text-xl font-semibold mb-4">
                    Players ({{ $game->players->count() }}/{{ $game->player_count }})
                </h2>

                <!-- Existing Players -->
                <div class="space-y-3 mb-6">
                    @forelse($game->players as $player)
                        <div class="flex items-center justify-between bg-slate-900 rounded-lg px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-4 h-4 rounded-full" style="background-color: {{ $player->color }}"></div>
                                <span class="font-medium">{{ $player->name }}</span>
                            </div>
                            <button wire:click="removePlayer('{{ $player->id }}')"
                                    class="text-red-400 hover:text-red-300 text-sm">
                                Remove
                            </button>
                        </div>
                    @empty
                        <p class="text-slate-400 text-sm">No players added yet.</p>
                    @endforelse
                </div>

                <!-- Add Player Form -->
                @if($game->players->count() < $game->player_count)
                    <div class="border-t border-slate-700 pt-4">
                        <h3 class="text-sm font-medium text-slate-300 mb-3">Add Player</h3>
                        <div class="flex gap-3 items-center">
                            <input type="text"
                                   wire:model="newPlayerName"
                                   placeholder="Player name"
                                   class="flex-1 bg-slate-900 border border-slate-600 rounded-lg px-4 py-2 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   wire:keydown.enter="addPlayer">

                            <input type="color"
                                   wire:model.live="newPlayerColor"
                                   class="w-10 h-10 cursor-pointer rounded-full border-2 border-slate-600 bg-transparent p-0 [&::-webkit-color-swatch-wrapper]:p-0 [&::-webkit-color-swatch]:rounded-full [&::-webkit-color-swatch]:border-0 [&::-moz-color-swatch]:rounded-full [&::-moz-color-swatch]:border-0">

                            <button wire:click="addPlayer"
                                    class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-lg font-medium transition">
                                Add
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Rounds Section -->
            <div class="bg-slate-800 rounded-xl p-6 border border-slate-700">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold">
                        Rounds ({{ $game->rounds->count() }}/{{ $game->total_rounds }})
                    </h2>
                    <button wire:click="openCategoryModal"
                            class="text-sm bg-blue-600 hover:bg-blue-700 px-3 py-1.5 rounded-lg font-medium transition">
                        + New Category
                    </button>
                </div>

                @if($categories->isEmpty())
                    <div class="bg-yellow-900/20 border border-yellow-700 text-yellow-400 px-4 py-3 rounded-lg mb-4">
                        No categories yet. <button wire:click="openCategoryModal" class="underline">Create one</button> to get started.
                    </div>
                @endif

                <div class="space-y-3">
                    @for($i = 1; $i <= $game->total_rounds; $i++)
                        <div class="flex items-center gap-3">
                            <span class="w-12 text-slate-400 text-sm">Round {{ $i }}</span>
                            <select x-data
                                    x-on:change="
                                        if ($event.target.value === '__new__') {
                                            $wire.openCategoryModal();
                                            $nextTick(() => { $event.target.value = '{{ $roundCategories[$i] ?? '' }}'; });
                                        } else {
                                            $wire.setRoundCategory({{ $i }}, $event.target.value);
                                        }
                                    "
                                    class="flex-1 bg-slate-900 border border-slate-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select category...</option>
                                @foreach($categories as $category)
                                    @php
                                        $isSelectedForThisRound = ($roundCategories[$i] ?? null) === $category->id;
                                        $isUsedInOtherRound = !$isSelectedForThisRound && in_array($category->id, $roundCategories);
                                    @endphp
                                    @if(!$isUsedInOtherRound)
                                        <option value="{{ $category->id }}"
                                                @selected($isSelectedForThisRound)>
                                            {{ $category->title }}
                                        </option>
                                    @endif
                                @endforeach
                                <option value="__new__">+ Create new category...</option>
                            </select>
                        </div>
                    @endfor
                </div>
            </div>
        </div>

        <!-- Start Game Button -->
        <div class="mt-8 text-center">
            @if($game->status === 'ready')
                <button wire:click="startGame"
                        class="bg-green-600 hover:bg-green-700 text-white px-12 py-4 rounded-xl text-xl font-bold transition">
                    Start Game
                </button>
                <p class="text-slate-400 mt-2">All players and rounds are configured!</p>
            @elseif($game->status === 'playing')
                <div class="flex justify-center gap-4">
                    <a href="{{ route('games.control', $game) }}"
                       class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-12 py-4 rounded-xl text-xl font-bold transition">
                        Continue Game
                    </a>
                    <button x-data
                            x-on:click="if (confirm('Are you sure you want to reset this game? All scores and progress will be cleared.')) { $wire.resetGame() }"
                            class="bg-red-600 hover:bg-red-700 text-white px-8 py-4 rounded-xl text-xl font-bold transition">
                        Reset Game
                    </button>
                </div>
            @else
                <button disabled
                        class="bg-slate-600 text-slate-400 px-12 py-4 rounded-xl text-xl font-bold cursor-not-allowed">
                    Start Game
                </button>
                <p class="text-slate-400 mt-2">
                    Add all {{ $game->player_count }} players and configure all {{ $game->total_rounds }} rounds to start.
                </p>
            @endif
        </div>
    </div>

    <!-- Category Modal -->
    @if($showCategoryModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
            <div class="flex min-h-screen items-center justify-center p-4">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-black/70 transition-opacity" wire:click="closeCategoryModal"></div>

                <!-- Modal -->
                <div class="relative bg-slate-800 rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto border border-slate-700">
                    <div class="sticky top-0 bg-slate-800 px-6 py-4 border-b border-slate-700 flex justify-between items-center">
                        <h3 class="text-xl font-bold">Create Category</h3>
                        <button wire:click="closeCategoryModal" class="text-slate-400 hover:text-white text-2xl">&times;</button>
                    </div>

                    <form wire:submit="saveCategory" class="p-6 space-y-6">
                        <!-- Category Info -->
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">Title</label>
                                <input type="text"
                                       wire:model="categoryTitle"
                                       placeholder="Top 10 Countries By Population"
                                       class="w-full bg-slate-900 border border-slate-600 rounded-lg px-4 py-2 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @error('categoryTitle') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-300 mb-2">Description (optional)</label>
                                <input type="text"
                                       wire:model="categoryDescription"
                                       placeholder="Source: 2024 World Population Review"
                                       class="w-full bg-slate-900 border border-slate-600 rounded-lg px-4 py-2 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <!-- Bulk Paste -->
                        <div class="bg-slate-900/50 rounded-lg p-4 border border-slate-700">
                            <label class="block text-sm font-medium text-slate-300 mb-2">Quick Import</label>
                            <p class="text-xs text-slate-500 mb-2">Paste a list (one per line). Use "Answer: stat" format to include stats.</p>
                            <div class="flex gap-2">
                                <textarea wire:model="bulkAnswers"
                                          rows="3"
                                          placeholder="1. China: 1.4 billion&#10;2. India: 1.4 billion&#10;3. United States: 334 million..."
                                          class="flex-1 bg-slate-900 border border-slate-600 rounded-lg px-3 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                                <button type="button"
                                        wire:click="parseBulkAnswers"
                                        class="px-4 py-2 bg-slate-700 hover:bg-slate-600 rounded-lg text-sm font-medium transition self-end">
                                    Import
                                </button>
                            </div>
                        </div>

                        <!-- Top 10 Answers -->
                        <div>
                            <h4 class="text-sm font-medium text-green-400 mb-3">Top 10 Answers (required)</h4>
                            <div class="space-y-2">
                                @for($i = 1; $i <= 10; $i++)
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 text-right text-sm text-green-400 font-medium">{{ $i }}.</span>
                                        <input type="text"
                                               wire:model="categoryAnswers.{{ $i }}"
                                               placeholder="Answer {{ $i }}"
                                               class="flex-1 bg-slate-900 border border-slate-600 rounded-lg px-3 py-1.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-green-500">
                                        <input type="text"
                                               wire:model="categoryAnswerStats.{{ $i }}"
                                               placeholder="Stat"
                                               class="w-24 bg-slate-900 border border-slate-600 rounded-lg px-2 py-1.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-green-500">
                                        <button type="button" wire:click="moveAnswerUp({{ $i }})"
                                                class="p-1.5 text-slate-500 hover:text-white hover:bg-slate-700 rounded transition {{ $i === 1 ? 'opacity-30 cursor-not-allowed' : '' }}"
                                                {{ $i === 1 ? 'disabled' : '' }}>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                        </button>
                                        <button type="button" wire:click="moveAnswerDown({{ $i }})"
                                                class="p-1.5 text-slate-500 hover:text-white hover:bg-slate-700 rounded transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                    </div>
                                @endfor
                            </div>
                            @error('categoryAnswers.*') <span class="text-red-400 text-sm block mt-2">{{ $message }}</span> @enderror
                        </div>

                        <!-- Tension Answers -->
                        <div>
                            <h4 class="text-sm font-medium text-red-400 mb-3">Tension Answers (optional, -5 pts each)</h4>
                            <div class="space-y-2">
                                @for($i = 11; $i <= 15; $i++)
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 text-right text-sm text-red-400 font-medium">{{ $i }}.</span>
                                        <input type="text"
                                               wire:model="categoryAnswers.{{ $i }}"
                                               placeholder="Tension {{ $i - 10 }}"
                                               class="flex-1 bg-slate-900 border border-slate-600 rounded-lg px-3 py-1.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-red-500">
                                        <input type="text"
                                               wire:model="categoryAnswerStats.{{ $i }}"
                                               placeholder="Stat"
                                               class="w-24 bg-slate-900 border border-slate-600 rounded-lg px-2 py-1.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-red-500">
                                        <button type="button" wire:click="moveAnswerUp({{ $i }})"
                                                class="p-1.5 text-slate-500 hover:text-white hover:bg-slate-700 rounded transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                        </button>
                                        <button type="button" wire:click="moveAnswerDown({{ $i }})"
                                                class="p-1.5 text-slate-500 hover:text-white hover:bg-slate-700 rounded transition {{ $i === 15 ? 'opacity-30 cursor-not-allowed' : '' }}"
                                                {{ $i === 15 ? 'disabled' : '' }}>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                    </div>
                                @endfor
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end gap-3 pt-4 border-t border-slate-700">
                            <button type="button"
                                    wire:click="closeCategoryModal"
                                    class="px-4 py-2 bg-slate-700 hover:bg-slate-600 rounded-lg font-medium transition">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg font-medium transition">
                                Create Category
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

<script data-navigate-once>
    document.addEventListener('livewire:initialized', function() {
        var gameId = '{{ $game->id }}';
        var channel = new BroadcastChannel('tension-game-' + gameId);

        Livewire.on('game-reset', function(params) {
            console.log('Broadcasting reset');
            channel.postMessage({ type: 'reset' });
        });
    });
</script>
