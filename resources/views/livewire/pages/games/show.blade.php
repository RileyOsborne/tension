<?php

use App\Models\Game;
use App\Models\Player;
use App\Models\Category;
use App\Models\Topic;
use App\Models\Answer;
use App\Models\Round;
use App\Services\GameStateMachine;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('components.layouts.app')] #[Title('Game Setup')] class extends Component {
    public Game $game;
    protected ?GameStateMachine $stateMachine = null;

    public string $gameName = '';
    public string $newPlayerName = '';
    public string $newPlayerColor = '#3B82F6';

    public array $roundCategories = [];

    public int $topAnswersCount;
    public int $frictionPenalty;
    public int $notOnListPenalty;
    public int $roundsPerPlayer;
    public int $doubleMultiplier;
    public int $doublesPerPlayer;
    public int $maxAnswersPerCategory;

    public bool $showAdvanced = false;

    public ?string $topicFilter = null;
    public string $searchQuery = '';

    public bool $showCategoryModal = false;
    public string $categoryTitle = '';
    public string $categoryDescription = '';
    public ?string $categoryTopicId = null;
    public string $newTopicName = '';
    public array $categoryAnswers = [];
    public array $categoryAnswerStats = [];
    public string $bulkAnswers = '';

    public bool $showRandomizeModal = false;
    public bool $showResetModal = false;

    public function mount(Game $game): void
    {
        $this->game = $game->load(['players', 'rounds.category']);
        $this->gameName = $game->name;

        $this->topAnswersCount = $game->top_answers_count;
        $this->frictionPenalty = $game->friction_penalty;
        $this->notOnListPenalty = $game->not_on_list_penalty;
        $this->roundsPerPlayer = $game->rounds_per_player;
        $this->doubleMultiplier = $game->double_multiplier;
        $this->doublesPerPlayer = $game->doubles_per_player;
        $this->maxAnswersPerCategory = $game->max_answers_per_category;

        $this->fixPlayerPositions();

        foreach ($game->rounds as $round) {
            $this->roundCategories[$round->round_number] = $round->category_id;
        }

        $this->resetCategoryForm();
        $this->checkReady();
    }

    public function updatedGameName(): void
    {
        $this->game->update(['name' => $this->gameName]);
    }

    public function updated($property): void
    {
        if (in_array($property, [
            'topAnswersCount', 'frictionPenalty', 'notOnListPenalty', 
            'roundsPerPlayer', 'doubleMultiplier', 'doublesPerPlayer', 
            'maxAnswersPerCategory'
        ])) {
            $this->game->update([
                'top_answers_count' => $this->topAnswersCount,
                'friction_penalty' => $this->frictionPenalty,
                'not_on_list_penalty' => $this->notOnListPenalty,
                'rounds_per_player' => $this->roundsPerPlayer,
                'double_multiplier' => $this->doubleMultiplier,
                'doubles_per_player' => $this->doublesPerPlayer,
                'max_answers_per_category' => $this->maxAnswersPerCategory,
            ]);
            
            $this->game->recalculateFromPlayers();
            $this->game->refresh();
            $this->checkReady();
        }
    }

    private function fixPlayerPositions(): void
    {
        $playersNeedingPositions = $this->game->players()->where('position', 0)->get();
        if ($playersNeedingPositions->isEmpty()) return;

        $maxPosition = $this->game->players()->max('position') ?? 0;
        foreach ($playersNeedingPositions as $player) {
            $maxPosition++;
            $player->update(['position' => $maxPosition]);
        }
        $this->game->refresh();
    }

    public function with(): array
    {
        $baseQuery = Category::with('topic')
            ->whereHas('answers', function ($q) {
                $q->where('position', '<=', 10);
            }, '>=', 10);

        $totalCategories = (clone $baseQuery)->count();

        $categories = (clone $baseQuery)
            ->when($this->topicFilter, fn($q) => $q->where('topic_id', $this->topicFilter))
            ->when($this->searchQuery, fn($q) => $q->where('title', 'like', "%{$this->searchQuery}%"))
            ->orderBy('title')
            ->get();

        $activePlayers = $this->game->players->filter(fn($p) => $p->isActive());
        $effectivePlayerCount = $activePlayers->count();
        $effectiveTotalRounds = $effectivePlayerCount * ($this->game->rounds_per_player ?? 2);

        return [
            'categories' => $categories,
            'totalCategories' => $totalCategories,
            'topics' => Topic::orderBy('name')->get(),
            'effectivePlayerCount' => $effectivePlayerCount,
            'effectiveTotalRounds' => $effectiveTotalRounds,
            'activePlayers' => $activePlayers,
        ];
    }

    public function clearFilters(): void
    {
        $this->topicFilter = null;
        $this->searchQuery = '';
    }

    protected function getStateMachine(): GameStateMachine
    {
        if (!$this->stateMachine) {
            $this->stateMachine = new GameStateMachine($this->game);
        }
        return $this->stateMachine;
    }

    public function generateJoinCode(): void
    {
        if (!$this->game->join_code) {
            $this->game->update(['join_code' => Game::generateJoinCode()]);
            $this->game->refresh();
        }
        $this->broadcastLobbyState();
    }

    public function broadcastLobbyState(): void
    {
        $this->getStateMachine()->refresh();
        $state = $this->getStateMachine()->broadcast();
        $this->dispatch('game-state-updated', state: $state);
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
        $this->categoryTopicId = null;
        $this->newTopicName = '';
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
            $clean = preg_replace('/^[\d]+[\.\)\-]\s*/', '', $line);
            $clean = preg_replace('/^[\-\*\•]\s*/', '', $clean);

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

    public function saveCategory(): void
    {
        $this->validate([
            'categoryTitle' => 'required|string|max:255',
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
        ]);

        $topicId = $this->categoryTopicId;
        if ($this->categoryTopicId === '__new__' && !empty($this->newTopicName)) {
            $topic = Topic::create(['name' => trim($this->newTopicName)]);
            $topicId = $topic->id;
        }

        $category = Category::create([
            'title' => $this->categoryTitle,
            'description' => $this->categoryDescription ?: null,
            'topic_id' => $topicId,
        ]);

        foreach ($this->categoryAnswers as $position => $text) {
            if (!empty(trim($text))) {
                Answer::create([
                    'category_id' => $category->id,
                    'text' => trim($text),
                    'stat' => trim($this->categoryAnswerStats[$position] ?? '') ?: null,
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

        $nextPosition = $this->game->players()->count() + 1;

        Player::create([
            'game_id' => $this->game->id,
            'name' => $this->newPlayerName,
            'color' => $this->newPlayerColor,
            'position' => $nextPosition,
        ]);

        $this->game->recalculateFromPlayers();
        $this->newPlayerName = '';
        $this->game->refresh();
        $this->checkReady();

        if ($this->game->join_code) {
            $this->broadcastLobbyState();
        }
    }

    public function removePlayer(Player $player): void
    {
        $player->delete();
        $this->game->refresh();
        $this->game->players()->orderBy('position')->get()->each(function ($p, $index) {
            $p->update(['position' => $index + 1]);
        });
        $this->game->recalculateFromPlayers();
        $this->game->refresh();
        $this->checkReady();

        if ($this->game->join_code) {
            $this->broadcastLobbyState();
        }
    }

    public function reorderPlayers(array $orderedIds): void
    {
        foreach ($orderedIds as $position => $playerId) {
            Player::where('id', $playerId)->update(['position' => $position + 1]);
        }
        $this->game->refresh();
    }

    public function setRoundCategory(int $roundNumber, ?string $categoryId): void
    {
        if ($categoryId === '__new__') {
            $this->openCategoryModal();
            return;
        }

        if (empty($categoryId)) {
            $this->game->rounds()->where('round_number', $roundNumber)->delete();
            unset($this->roundCategories[$roundNumber]);
        } else {
            Round::updateOrCreate(
                ['game_id' => $this->game->id, 'round_number' => $roundNumber],
                ['category_id' => $categoryId]
            );
            $this->roundCategories[$roundNumber] = $categoryId;
        }

        $this->game->refresh();
        $this->checkReady();
    }

    public function randomCategory(int $roundNumber): void
    {
        $usedCategoryIds = array_values($this->roundCategories);
        $currentSelection = $this->roundCategories[$roundNumber] ?? null;

        $available = Category::with('topic')
            ->whereHas('answers', function ($q) {
                $q->where('position', '<=', 10);
            }, '>=', 10)
            ->when($this->topicFilter, fn($q) => $q->where('topic_id', $this->topicFilter))
            ->when($this->searchQuery, fn($q) => $q->where('title', 'like', "%{$this->searchQuery}%"))
            ->whereNotIn('id', array_filter($usedCategoryIds, fn($id) => $id !== $currentSelection))
            ->inRandomOrder()
            ->first();

        if ($available) {
            $this->setRoundCategory($roundNumber, $available->id);
        }
    }

    public function tryRandomizeAll(): void
    {
        if (!empty(array_filter($this->roundCategories))) {
            $this->showRandomizeModal = true;
            return;
        }
        $this->randomizeAllCategories();
    }

    public function randomizeAllCategories(): void
    {
        $this->showRandomizeModal = false;
        $activePlayers = $this->game->players->filter(fn($p) => $p->isActive());
        $effectiveTotalRounds = $activePlayers->count() * ($this->game->rounds_per_player ?? 2);

        $available = Category::with('topic')
            ->whereHas('answers', function ($q) {
                $q->where('position', '<=', 10);
            }, '>=', 10)
            ->when($this->topicFilter, fn($q) => $q->where('topic_id', $this->topicFilter))
            ->when($this->searchQuery, fn($q) => $q->where('title', 'like', "%{$this->searchQuery}%"))
            ->inRandomOrder()
            ->limit($effectiveTotalRounds)
            ->get();

        $this->roundCategories = [];
        foreach ($available as $index => $category) {
            $roundNumber = $index + 1;
            Round::updateOrCreate(
                ['game_id' => $this->game->id, 'round_number' => $roundNumber],
                ['category_id' => $category->id]
            );
            $this->roundCategories[$roundNumber] = $category->id;
        }

        $this->game->rounds()->where('round_number', '>', $effectiveTotalRounds)->delete();
        $this->game->refresh();
        $this->checkReady();
    }

    public function refreshPlayerStatus(): void
    {
        $this->game->load('players');
        if ($this->game->join_code) {
            $this->broadcastLobbyState();
        }
        $this->checkReady();
    }

    public function checkReady(): void
    {
        $activePlayers = $this->game->players->filter(fn($p) => $p->isActive());
        $effectivePlayerCount = $activePlayers->count();
        $effectiveTotalRounds = $effectivePlayerCount * ($this->game->rounds_per_player ?? 2);

        if ($this->game->player_count !== $effectivePlayerCount) {
            $this->game->recalculateFromPlayers();
            $this->game->refresh();
        }

        $hasPlayers = $effectivePlayerCount > 0;
        $roundsReady = $this->game->rounds()->count() >= $effectiveTotalRounds;

        if ($hasPlayers && $roundsReady && $this->game->status === 'draft') {
            $this->game->update(['status' => 'ready']);
        } elseif ((!$hasPlayers || !$roundsReady) && $this->game->status === 'ready') {
            $this->game->update(['status' => 'draft']);
        }
    }

    public function startGame(): void
    {
        if ($this->game->status !== 'ready') return;

        if (!$this->game->join_code) {
            $this->game->update(['join_code' => Game::generateJoinCode()]);
        }

        $this->game->update(['current_round' => 1]);
        $this->game->refresh();
        $this->getStateMachine()->startGame();
        $this->redirect(route('games.control', $this->game), navigate: true);
    }

    public function resetGame(): void
    {
        $this->showResetModal = false;
        $this->game->players()->update(['total_score' => 0, 'double_used' => false]);
        foreach ($this->game->rounds as $round) {
            $round->playerAnswers()->delete();
        }
        $this->game->rounds()->update(['status' => 'pending', 'current_slide' => 0]);
        $this->game->update(['status' => 'ready', 'current_round' => 1]);
        $this->game->refresh();
        $this->dispatch('game-reset', gameId: $this->game->id);
    }
}; ?>

<div class="min-h-screen bg-slate-950 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white antialiased flex flex-col" wire:poll.3s="refreshPlayerStatus">
    <div class="max-w-[1600px] w-full mx-auto px-12 py-12 flex flex-col flex-1">
        
        <!-- Integrated Header -->
        <div class="flex items-center mb-12">
            <a href="{{ route('games.index') }}" class="group text-slate-400 hover:text-white transition flex items-center gap-4 font-black uppercase tracking-[0.4em] text-lg">
                <svg class="w-6 h-6 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15 19l-7-7 7-7"/></svg>
                Dashboard
            </a>
        </div>

        <!-- Hero Section -->
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center gap-8 group relative" x-data="{ name: @entangle('gameName') }">
                <div class="flex items-center gap-3">
                    <input type="text"
                           wire:model.live.debounce.500ms="gameName"
                           x-model="name"
                           :style="{ width: (name.length + 1) + 'ch' }"
                           class="bg-transparent border-0 border-b-4 border-transparent hover:border-slate-800 focus:border-blue-600 focus:ring-0 p-0 text-7xl font-black text-white text-center transition tracking-tighter uppercase leading-none min-w-[150px]"
                           placeholder="GAME NAME...">
                    <svg class="w-6 h-6 text-slate-500 opacity-40 group-hover:opacity-100 transition-opacity flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                </div>

                <span class="px-8 py-3 rounded-2xl font-black uppercase tracking-[0.2em] text-xs border border-white/10 shadow-2xl bg-slate-900/80 shrink-0
                    @switch($game->status)
                        @case('draft') text-slate-500 @break
                        @case('ready') text-blue-500 @break
                        @case('playing') text-green-500 @break
                    @endswitch
                ">
                    Status: {{ $game->status }}
                </span>
            </div>
        </div>

        <!-- Single Row Dashboard -->
        <div class="grid lg:grid-cols-12 gap-8 items-start">
            <!-- Column 1: Participants -->
            <div class="lg:col-span-4">
                <div class="bg-slate-900/40 rounded-[2.5rem] p-8 border border-white/5 shadow-xl backdrop-blur-xl flex flex-col">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-black uppercase tracking-tight">Players</h2>
                        <span class="text-slate-500 font-black uppercase tracking-widest text-[10px]">{{ $activePlayers->count() }} ACTIVE</span>
                    </div>

                    <div class="space-y-4 mb-10">
                        @forelse($activePlayers as $player)
                            <div class="flex items-center justify-between bg-slate-800/50 border-2 border-slate-700 rounded-[1.5rem] p-5 group transition hover:border-slate-600 shadow-inner">
                                <div class="flex items-center gap-5">
                                    <div class="drag-handle cursor-grab text-slate-600 hover:text-slate-400 transition">
                                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4 8h16M4 16h16"/></svg>
                                    </div>
                                    <div class="w-6 h-6 rounded-full border-2 border-white/10 shadow-lg" style="background-color: {{ $player->color }}"></div>
                                    <span class="font-black text-2xl tracking-tight uppercase" style="color: {{ $player->color }}">{{ $player->name }}</span>
                                </div>
                                <button wire:click="removePlayer('{{ $player->id }}')" class="opacity-0 group-hover:opacity-100 text-slate-600 hover:text-red-500 transition px-1">
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        @empty
                            <div class="py-10 border-2 border-dashed border-slate-800 rounded-2xl text-center bg-slate-900/60 shadow-inner">
                                <p class="text-slate-600 font-black uppercase tracking-widest text-[10px]">No players</p>
                            </div>
                        @endforelse
                    </div>

                    <div class="pt-8 border-t border-white/5 space-y-4">
                        <div class="flex gap-3">
                            <input type="text"
                                   wire:model="newPlayerName"
                                   placeholder="PLAYER NAME..."
                                   class="flex-1 bg-slate-800/40 border-2 border-slate-800 rounded-[1.5rem] px-6 py-4 text-lg text-white font-black uppercase tracking-tight placeholder-slate-700 focus:border-blue-600 focus:ring-0 transition shadow-inner">
                            
                            <div class="relative w-16 h-16 shrink-0" x-data="{ color: @entangle('newPlayerColor') }">
                                <input type="color" x-model="color" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                                <div class="w-full h-full rounded-[1.5rem] border-2 border-slate-800 bg-slate-900/60 p-1 flex items-center justify-center shadow-inner">
                                    <div class="w-full h-full rounded-xl border border-white/10 shadow-lg" :style="`background-color: ${color}`"></div>
                                </div>
                            </div>
                        </div>
                        <button wire:click="addPlayer" class="w-full bg-blue-600 border-b-8 border-blue-800 hover:bg-blue-500 active:translate-y-1 active:border-b-0 text-white py-5 rounded-[2rem] text-xl font-black uppercase tracking-widest transition-all shadow-2xl">
                            Add Player
                        </button>
                    </div>
                </div>
            </div>

            <!-- Column 2: Roadmap -->
            <div class="lg:col-span-5 flex flex-col">
                <div class="bg-slate-900/40 rounded-[2.5rem] p-10 border border-white/5 shadow-2xl backdrop-blur-xl flex flex-col">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h2 class="text-3xl font-black uppercase tracking-tight">Game Roadmap</h2>
                            <p class="text-slate-500 font-bold text-[10px] uppercase tracking-widest mt-1">{{ $effectiveTotalRounds }} ROUNDS SCHEDULED</p>
                        </div>
                        <div class="flex gap-4">
                            <button wire:click="tryRandomizeAll" class="bg-slate-800 border-b-4 border-slate-950 hover:bg-slate-700 text-slate-300 px-6 py-3 rounded-2xl font-black uppercase tracking-widest text-xs transition-all active:translate-y-1 active:border-b-0 flex items-center gap-3">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="4"/><circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/><circle cx="15.5" cy="15.5" r="1.5" fill="currentColor"/></svg>
                                Randomize All
                            </button>
                            <button wire:click="openCategoryModal" class="bg-blue-600 border-b-4 border-blue-800 hover:bg-blue-500 text-white px-6 py-3 rounded-2xl font-black uppercase tracking-widest text-xs transition-all active:translate-y-1 active:border-b-0">
                                + New Category
                            </button>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="grid md:grid-cols-2 gap-6 mb-8">
                        <div class="relative">
                            <input type="text" 
                                   wire:model.live.debounce.200ms="searchQuery"
                                   placeholder="SEARCH CONTENT..."
                                   class="w-full bg-slate-800/40 border-2 border-slate-800 rounded-2xl pl-12 pr-6 py-4 text-sm font-black text-white placeholder-slate-700 focus:border-blue-600 focus:ring-0 transition shadow-inner">
                            <svg class="w-5 h-5 text-slate-700 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <select wire:model.live="topicFilter" 
                                class="w-full bg-slate-800/40 border-2 border-slate-800 rounded-2xl px-6 py-4 text-sm font-black text-white focus:border-blue-600 focus:ring-0 transition uppercase cursor-pointer shadow-inner appearance-none">
                            <option value="">ALL TOPIC GROUPS</option>
                            @foreach($topics as $topic)
                                <option value="{{ $topic->id }}">{{ strtoupper($topic->name) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Rounds Timeline -->
                    <div class="flex-1 space-y-4 max-h-[400px] overflow-y-auto pr-4 custom-scrollbar">
                        @for($i = 1; $i <= max($effectiveTotalRounds, 4); $i++)
                            @php
                                $selectedCategoryId = $roundCategories[$i] ?? null;
                                $isSkeleton = $i > $effectiveTotalRounds;
                                $availableCategories = $categories->filter(function($cat) use ($roundCategories, $i, $selectedCategoryId) {
                                    if ($cat->id === $selectedCategoryId) return true;
                                    return !in_array($cat->id, array_values($roundCategories));
                                });
                            @endphp
                            
                            <div class="flex items-center gap-6 group {{ $isSkeleton ? 'opacity-10 grayscale scale-[0.98]' : '' }}">
                                <div class="w-14 h-14 flex-shrink-0 rounded-[1.5rem] bg-slate-900/60 border-2 {{ $selectedCategoryId ? 'border-green-500 shadow-[0_0_20px_rgba(34,197,94,0.2)]' : 'border-slate-800' }} flex items-center justify-center font-black text-xl text-slate-600 transition-all shadow-inner">
                                    {{ $i }}
                                </div>
                                
                                @if($isSkeleton)
                                    <div class="flex-1 h-[68px] bg-slate-900 border-2 border-slate-800 border-dashed rounded-[1.5rem] flex items-center px-8 shadow-inner">
                                        <div class="w-1/3 h-2.5 bg-slate-800 rounded-full animate-pulse"></div>
                                    </div>
                                    <div class="w-14 h-14 rounded-[1.5rem] bg-slate-900 border-2 border-slate-800"></div>
                                @else
                                    <div class="flex-1 relative">
                                        <select wire:change="setRoundCategory({{ $i }}, $event.target.value)"
                                                class="w-full bg-slate-800/40 border-2 {{ $selectedCategoryId ? 'border-blue-600 text-white shadow-[0_0_15px_rgba(59,130,246,0.1)]' : 'border-slate-800 border-dashed text-slate-700 italic' }} rounded-[1.5rem] pl-6 pr-14 py-4 text-lg font-black uppercase tracking-tighter transition hover:border-slate-600 appearance-none cursor-pointer focus:ring-0 shadow-inner truncate">
                                            <option value="">SELECT CONTENT...</option>
                                            @foreach($availableCategories as $category)
                                                <option value="{{ $category->id }}" {{ $selectedCategoryId === $category->id ? 'selected' : '' }}>
                                                    {{ $category->title }}
                                                </option>
                                            @endforeach
                                            <option value="__new__" class="text-blue-500 font-black">+ CREATE NEW CONTENT</option>
                                        </select>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-6 pointer-events-none text-slate-700">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                                        </div>
                                    </div>
                                    <button wire:click="randomCategory({{ $i }})"
                                            class="w-14 h-14 flex-shrink-0 rounded-[1.5rem] bg-slate-800 border-b-4 border-slate-950 hover:bg-slate-700 flex items-center justify-center text-white transition-all active:translate-y-1 active:border-b-0 shadow-xl">
                                        <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="4"/><circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/><circle cx="15.5" cy="15.5" r="1.5" fill="currentColor"/></svg>
                                    </button>
                                @endif
                            </div>
                        @endfor
                    </div>

                    <!-- Actions -->
                    <div class="mt-12 pt-10 border-t border-white/5 flex justify-center gap-8">
                        <a href="{{ route('games.present', $game) }}" target="friction-presentation"
                           class="w-64 bg-purple-600 border-b-8 border-purple-800 hover:bg-purple-500 text-white py-4 px-10 rounded-[2rem] text-xl font-black uppercase tracking-widest transition-all active:translate-y-1 active:border-b-0 shadow-2xl text-center flex items-center justify-center">
                            Presentation
                        </a>
                        
                        @if($game->status === 'ready')
                            <button wire:click="startGame"
                                    class="w-64 bg-green-600 border-b-8 border-green-800 hover:bg-green-500 text-white py-4 px-10 rounded-[2rem] text-xl font-black uppercase tracking-widest transition-all active:translate-y-1 active:border-b-0 shadow-2xl shadow-green-600/20">
                                Start Game
                            </button>
                        @elseif($game->status === 'playing')
                            <a href="{{ route('games.control', $game) }}"
                               class="w-64 bg-blue-600 border-b-8 border-blue-800 hover:bg-blue-500 text-white py-4 px-10 rounded-[2rem] text-xl font-black uppercase tracking-widest transition-all active:translate-y-1 active:border-b-0 text-center flex items-center justify-center">
                                Resume Game
                            </a>
                        @else
                            <div class="w-64 bg-slate-900 border-4 border-dashed border-slate-800 rounded-[2rem] flex items-center justify-center py-4 opacity-40 shadow-inner">
                                <p class="text-xs font-black uppercase tracking-widest text-slate-600 text-center">Setup incomplete</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Column 3: Rules & Scoring -->
            <div class="lg:col-span-3">
                <div class="bg-slate-900/40 rounded-[2.5rem] p-8 border border-white/5 shadow-2xl backdrop-blur-xl h-auto flex flex-col">
                    <div class="flex items-center justify-between mb-8">
                        <h3 class="text-xl font-black uppercase tracking-tight text-slate-400">Rules & Scoring</h3>
                        <button wire:click="confirmReset" class="p-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 transition border border-white/5 shadow-inner group" title="Reset Session">
                            <svg class="w-5 h-5 group-hover:rotate-180 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </button>
                    </div>
                    
                    <div class="space-y-6">
                        <div class="space-y-3">
                            <label class="block text-xs font-black uppercase tracking-widest text-slate-600 border-b border-slate-800 pb-2">Scoring</label>
                            <div class="flex items-center justify-between bg-slate-800/40 p-4 rounded-2xl border-2 border-slate-800 shadow-inner">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Top 10 Answers</span>
                                <input type="number" wire:model.live="topAnswersCount" class="bg-transparent border-0 text-right font-black text-xl text-blue-500 focus:ring-0 w-16 p-0">
                            </div>
                            <div class="flex items-center justify-between bg-slate-800/40 p-4 rounded-2xl border-2 border-slate-800 shadow-inner">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Double Multiplier</span>
                                <input type="number" wire:model.live="doubleMultiplier" class="bg-transparent border-0 text-right font-black text-xl text-blue-500 focus:ring-0 w-16 p-0">
                            </div>
                        </div>
                        <div class="space-y-3">
                            <label class="block text-xs font-black uppercase tracking-widest text-slate-600 border-b border-slate-800 pb-2">Penalties</label>
                            <div class="flex items-center justify-between bg-slate-800/40 p-4 rounded-2xl border-2 border-slate-800 shadow-inner">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Friction Penalty</span>
                                <input type="number" wire:model.live="frictionPenalty" class="bg-transparent border-0 text-right font-black text-xl text-red-500 focus:ring-0 w-16 p-0">
                            </div>
                            <div class="flex items-center justify-between bg-slate-800/40 p-4 rounded-2xl border-2 border-slate-800 shadow-inner">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Not On List Penalty</span>
                                <input type="number" wire:model.live="notOnListPenalty" class="bg-transparent border-0 text-right font-black text-xl text-red-500 focus:ring-0 w-16 p-0">
                            </div>
                        </div>
                        <div class="space-y-3">
                            <label class="block text-xs font-black uppercase tracking-widest text-slate-600 border-b border-slate-800 pb-2">Structure</label>
                            <div class="flex items-center justify-between bg-slate-800/40 p-4 rounded-2xl border-2 border-slate-800 shadow-inner">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Rounds Per Player</span>
                                <input type="number" wire:model.live="roundsPerPlayer" class="bg-transparent border-0 text-right font-black text-xl text-purple-500 focus:ring-0 w-16 p-0">
                            </div>
                            <div class="flex items-center justify-between bg-slate-800/40 p-4 rounded-2xl border-2 border-slate-800 shadow-inner">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Doubles Per Player</span>
                                <input type="number" wire:model.live="doublesPerPlayer" class="bg-transparent border-0 text-right font-black text-xl text-purple-500 focus:ring-0 w-16 p-0">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    @if($showCategoryModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-950/90 backdrop-blur-xl transition-opacity" wire:click="closeCategoryModal"></div>
            <div class="relative bg-slate-900 rounded-[3rem] shadow-2xl w-full max-w-4xl max-h-[95vh] overflow-hidden border border-white/5 flex flex-col">
                <div class="px-12 py-10 border-b border-white/5 flex justify-between items-center bg-slate-900/50">
                    <h3 class="text-5xl font-black tracking-tighter uppercase text-white leading-none">CREATE CONTENT</h3>
                    <button wire:click="closeCategoryModal" class="text-slate-400 hover:text-white transition">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit="saveCategory" class="flex-1 overflow-y-auto p-16 space-y-16 custom-scrollbar">
                    <div class="space-y-10">
                        <input type="text" wire:model="categoryTitle" placeholder="CATEGORY TITLE" class="w-full bg-slate-800/40 border-2 border-slate-800 rounded-[2rem] px-10 py-8 text-4xl font-black text-white focus:border-blue-600 focus:ring-0 uppercase tracking-tight shadow-inner">
                        <select wire:model.live="categoryTopicId" class="w-full bg-slate-800/40 border-2 border-slate-800 rounded-[2rem] px-10 py-8 text-2xl text-white font-black uppercase appearance-none cursor-pointer focus:border-blue-600 shadow-inner">
                            <option value="">NO TOPIC GROUP</option>
                            @foreach($topics as $topic) <option value="{{ $topic->id }}">{{ strtoupper($topic->name) }}</option> @endforeach
                            <option value="__new__">+ CREATE NEW TOPIC...</option>
                        </select>
                    </div>

                    <div class="bg-blue-900/10 p-12 rounded-[3rem] border border-blue-500/20 shadow-inner">
                        <label class="block text-xs font-black uppercase tracking-[0.3em] text-blue-400 mb-6">Smart Paste Import</label>
                        <div class="flex gap-6">
                            <textarea wire:model="bulkAnswers" rows="4" placeholder="1. Item One: 100&#10;2. Item Two: 95..." class="flex-1 bg-slate-800/40 border-2 border-slate-800 rounded-[2rem] px-8 py-6 text-xl text-white font-bold focus:border-blue-600 resize-none shadow-inner"></textarea>
                            <button type="button" wire:click="parseBulkAnswers" class="bg-blue-600 hover:bg-blue-500 border-b-8 border-blue-800 active:translate-y-1 active:border-b-0 text-white px-12 rounded-[2rem] font-black uppercase tracking-widest text-lg transition-all self-stretch">Parse</button>
                        </div>
                    </div>

                    <div class="space-y-8">
                        <h4 class="text-sm font-black uppercase tracking-[0.4em] text-green-500 flex items-center gap-4">
                            <span class="w-4 h-4 rounded-full bg-green-500 shadow-[0_0_15px_rgba(34,197,94,0.5)]"></span> THE TOP 10
                        </h4>
                        <div class="grid grid-cols-1 gap-6">
                            @for($i = 1; $i <= 10; $i++)
                                <div class="flex items-center gap-6">
                                    <span class="w-12 font-black text-slate-700 text-4xl text-right uppercase leading-none">{{ $i }}</span>
                                    <input type="text" wire:model="categoryAnswers.{{ $i }}" class="flex-1 bg-slate-800/40 border-2 border-slate-800 rounded-[1.5rem] px-8 py-6 text-2xl font-black text-white uppercase tracking-tight focus:border-blue-600 shadow-inner">
                                    <input type="text" wire:model="categoryAnswerStats.{{ $i }}" placeholder="STAT" class="w-40 bg-slate-800/40 border-2 border-slate-800 rounded-[1.5rem] px-8 py-6 text-lg font-black text-slate-500 uppercase focus:border-blue-600 shadow-inner">
                                </div>
                            @endfor
                        </div>
                    </div>

                    <div class="flex gap-8 pt-16 border-t border-white/5">
                        <button type="button" wire:click="closeCategoryModal" class="flex-1 py-8 rounded-[2rem] font-black uppercase tracking-widest text-xl text-slate-500 hover:text-white transition">Cancel</button>
                        <button type="submit" class="flex-[2] bg-blue-600 border-b-8 border-blue-800 hover:bg-blue-500 active:translate-y-1 active:border-b-0 text-white py-8 rounded-[2rem] font-black uppercase tracking-widest text-xl transition-all shadow-2xl">Save Content</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if($showRandomizeModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-950/90 backdrop-blur-md" wire:click="closeRandomizeModal"></div>
            <div class="relative bg-slate-900 rounded-[3rem] p-12 max-w-md w-full border border-slate-700 text-center shadow-2xl">
                <h3 class="text-3xl font-black uppercase tracking-tight mb-4 text-white">Shuffle All?</h3>
                <p class="text-slate-400 mb-10 font-bold uppercase tracking-widest text-sm">Replace all rounds with random categories.</p>
                <div class="flex flex-col gap-4">
                    <button wire:click="randomizeAllCategories" class="w-full bg-blue-600 border-b-8 border-blue-800 active:translate-y-1 active:border-b-0 text-white py-5 rounded-2xl font-black uppercase tracking-widest transition-all">Confirm Shuffle</button>
                    <button wire:click="closeRandomizeModal" class="w-full py-4 rounded-xl font-black uppercase tracking-widest text-slate-500 hover:text-white transition">Cancel</button>
                </div>
            </div>
        </div>
    @endif

    @if($showResetModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-slate-950/90 backdrop-blur-xl transition-opacity" wire:click="closeResetModal"></div>
            <div class="relative bg-slate-900 rounded-[3rem] p-12 max-w-md w-full border border-red-900/50 text-center shadow-2xl">
                <h3 class="text-3xl font-black uppercase tracking-tight text-red-500 mb-4">Wipe Data?</h3>
                <p class="text-slate-400 mb-10 font-bold uppercase tracking-widest text-sm">Permanently erases all scores and progress.</p>
                <div class="flex flex-col gap-4">
                    <button wire:click="resetGame" class="w-full bg-red-600 border-b-8 border-red-800 active:translate-y-1 active:border-b-0 text-white py-5 rounded-2xl font-black uppercase tracking-widest transition-all">Confirm Wipe</button>
                    <button wire:click="closeResetModal" class="w-full py-4 rounded-xl font-black uppercase tracking-widest text-slate-500 hover:text-white transition">Cancel</button>
                </div>
            </div>
        </div>
    @endif

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 12px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.05); border-radius: 10px; border: 3px solid transparent; background-clip: padding-box; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.1); border: 3px solid transparent; background-clip: padding-box; }
    </style>

    <script data-navigate-once>
        document.addEventListener('livewire:initialized', function() {
            var gameId = '{{ $game->id }}';
            var channel = new BroadcastChannel('friction-game-' + gameId);

            Livewire.on('game-reset', function(params) {
                channel.postMessage({ type: 'reset' });
            });

            document.addEventListener('game-state-updated', function(event) {
                var state = event.detail.state || event.detail;
                channel.postMessage(state);
            });

            channel.onmessage = function(event) {
                if (event.data && event.data.type === 'request-state') {
                    var component = Livewire.all()[0];
                    if (component && component.$wire.broadcastLobbyState) {
                        component.$wire.broadcastLobbyState();
                    }
                }
            };

            if (window.Echo) {
                window.Echo.channel('game.' + gameId)
                    .listen('.player.joined', function(e) {
                        var component = Livewire.all()[0];
                        if (component && component.$wire) {
                            component.$wire.$refresh().then(function() {
                                if (component.$wire.broadcastLobbyState) {
                                    component.$wire.broadcastLobbyState();
                                }
                            });
                        }
                    })
                    .listen('.player.left', function(e) {
                        var component = Livewire.all()[0];
                        if (component && component.$wire) {
                            component.$wire.$refresh().then(function() {
                                if (component.$wire.broadcastLobbyState) {
                                    component.$wire.broadcastLobbyState();
                                }
                            });
                        }
                    });
            }
        });
    </script>
</div>
