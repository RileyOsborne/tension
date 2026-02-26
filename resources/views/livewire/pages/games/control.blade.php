<?php

use App\Models\Game;
use App\Models\Player;
use App\Models\Round;
use App\Models\Category;
use App\Models\Answer;
use App\Models\PlayerAnswer;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\On;

new #[Layout('components.layouts.app')] #[Title('Game Master Control')] class extends Component {
    public Game $game;
    public ?Round $currentRound = null;

    public bool $showRules = false;

    // Player answer collection
    public array $playerAnswers = []; // player_id => answer text
    public array $playerDoubles = []; // player_id => bool (using double)

    // Reveal tracking
    public int $revealedCount = 0; // How many answers have been revealed (1-15)

    #[On('broadcast-state')]
    public function handleBroadcastState(): void
    {
        $this->broadcastState();
    }

    public function mount(Game $game): void
    {
        $this->game = $game->load(['players', 'rounds.category.answers']);

        // Check if we need to show rules (round 1, slide 0)
        if ($game->current_round === 1) {
            $round = $game->rounds()->where('round_number', 1)->first();
            if ($round && $round->current_slide === 0 && $round->status === 'intro') {
                $this->showRules = true;
            }
        }

        $this->loadCurrentState();
    }

    public function loadCurrentState(): void
    {
        $this->currentRound = $this->game->rounds()
            ->where('round_number', $this->game->current_round)
            ->with(['category.answers', 'playerAnswers.player', 'playerAnswers.answer'])
            ->first();

        // Load existing player answers for this round
        if ($this->currentRound) {
            $this->revealedCount = $this->currentRound->current_slide;

            // Initialize player answers array
            foreach ($this->game->players as $player) {
                $existing = $this->currentRound->playerAnswers->where('player_id', $player->id)->first();
                if ($existing && $existing->answer) {
                    $this->playerAnswers[$player->id] = $existing->answer->text;
                } elseif ($existing && !$existing->answer_id) {
                    $this->playerAnswers[$player->id] = '__not_on_list__';
                } else {
                    $this->playerAnswers[$player->id] = '';
                }
                $this->playerDoubles[$player->id] = false;
            }
        }
    }

    public function with(): array
    {
        $answers = $this->currentRound?->category->answers->sortBy('position') ?? collect();

        // Get revealed answers (positions 1 to revealedCount)
        $revealedAnswers = $answers->filter(fn($a) => $a->position <= $this->revealedCount);

        // Build player answers map for display
        $playerAnswerMap = [];
        if ($this->currentRound) {
            foreach ($this->currentRound->playerAnswers as $pa) {
                $playerAnswerMap[$pa->player_id] = [
                    'answer' => $pa->answer,
                    'answer_text' => $pa->answer?->text ?? 'Not on list',
                    'points' => $pa->points_awarded,
                    'was_doubled' => $pa->was_doubled,
                ];
            }
        }

        return [
            'players' => $this->game->players,
            'answers' => $answers,
            'revealedAnswers' => $revealedAnswers,
            'playerAnswerMap' => $playerAnswerMap,
            'allAnswersCollected' => $this->allAnswersCollected(),
            'answerOptions' => $answers->pluck('text', 'id')->toArray(),
        ];
    }

    public function allAnswersCollected(): bool
    {
        if (!$this->currentRound) return false;

        foreach ($this->game->players as $player) {
            if (empty($this->playerAnswers[$player->id] ?? '')) {
                return false;
            }
        }
        return true;
    }

    /**
     * Find a matching answer using fuzzy matching.
     * Handles cases like "El Fuego" matching "Fuego" or "The Beatles" matching "Beatles".
     */
    public function findMatchingAnswer(string $input): ?Answer
    {
        if (!$this->currentRound) return null;

        $input = trim($input);
        $inputLower = strtolower($input);
        $inputNormalized = $this->normalizeForMatching($input);

        // First try exact match against full text (case-insensitive)
        foreach ($this->currentRound->category->answers as $answer) {
            if (strtolower($answer->text) === $inputLower) {
                return $answer;
            }
        }

        // Try exact match against display_text (for geo answers)
        foreach ($this->currentRound->category->answers as $answer) {
            if (strtolower($answer->display_text) === $inputLower) {
                return $answer;
            }
        }

        // Try normalized match against both text and display_text
        foreach ($this->currentRound->category->answers as $answer) {
            $answerNormalized = $this->normalizeForMatching($answer->text);
            $displayNormalized = $this->normalizeForMatching($answer->display_text);

            if ($answerNormalized === $inputNormalized || $displayNormalized === $inputNormalized) {
                return $answer;
            }
        }

        // Try containment match (input contains answer or answer contains input)
        foreach ($this->currentRound->category->answers as $answer) {
            $answerNormalized = $this->normalizeForMatching($answer->text);
            $displayNormalized = $this->normalizeForMatching($answer->display_text);

            // Check if one is contained in the other (for "El Fuego" vs "Fuego")
            foreach ([$answerNormalized, $displayNormalized] as $targetNormalized) {
                if (str_contains($inputNormalized, $targetNormalized) || str_contains($targetNormalized, $inputNormalized)) {
                    // Only match if the shorter string is at least 4 chars to avoid false positives
                    $shorter = strlen($inputNormalized) < strlen($targetNormalized) ? $inputNormalized : $targetNormalized;
                    if (strlen($shorter) >= 4) {
                        return $answer;
                    }
                }
            }
        }

        // Try similarity match using Levenshtein distance
        $bestMatch = null;
        $bestScore = PHP_INT_MAX;

        foreach ($this->currentRound->category->answers as $answer) {
            foreach ([$answer->text, $answer->display_text] as $target) {
                $targetNormalized = $this->normalizeForMatching($target);

                // Calculate Levenshtein distance
                $distance = levenshtein($inputNormalized, $targetNormalized);

                // Allow max 2 character difference for strings > 5 chars, or 1 for shorter
                $maxDistance = strlen($targetNormalized) > 5 ? 2 : 1;

                if ($distance <= $maxDistance && $distance < $bestScore) {
                    $bestScore = $distance;
                    $bestMatch = $answer;
                }
            }
        }

        return $bestMatch;
    }

    /**
     * Normalize a string for matching by removing articles, punctuation, and extra spaces.
     */
    private function normalizeForMatching(string $text): string
    {
        $text = strtolower(trim($text));

        // Remove common articles/prefixes in various languages
        $articles = ['the ', 'a ', 'an ', 'el ', 'la ', 'los ', 'las ', 'le ', 'les ', 'der ', 'die ', 'das '];
        foreach ($articles as $article) {
            if (str_starts_with($text, $article)) {
                $text = substr($text, strlen($article));
                break;
            }
        }

        // Remove punctuation and extra spaces
        $text = preg_replace('/[^\w\s]/', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    public function dismissRules(): void
    {
        $this->showRules = false;
        $this->broadcastState();
    }

    public function startCollecting(): void
    {
        if (!$this->currentRound) return;

        $this->currentRound->update(['status' => 'collecting']);
        $this->broadcastState();
    }

    public function submitPlayerAnswer(string $playerId): void
    {
        if (!$this->currentRound) return;

        $answerText = trim($this->playerAnswers[$playerId] ?? '');
        if (empty($answerText)) return;

        $player = Player::find($playerId);
        if (!$player) return;

        // Delete any existing answer for this player in this round
        PlayerAnswer::where('round_id', $this->currentRound->id)
            ->where('player_id', $playerId)
            ->delete();

        // Find matching answer using fuzzy matching
        $answer = $this->findMatchingAnswer($answerText);

        // If match found, use its points; otherwise it's "not on list" = -3
        $points = $answer ? $answer->points : -3;

        // Apply double if selected
        $wasDoubled = $this->playerDoubles[$playerId] ?? false;
        if ($wasDoubled && $player->canUseDouble()) {
            $points *= 2;
            $player->update(['double_used' => true]);
        }

        // Create player answer record
        PlayerAnswer::create([
            'round_id' => $this->currentRound->id,
            'player_id' => $playerId,
            'answer_id' => $answer?->id,
            'points_awarded' => $points,
            'was_doubled' => $wasDoubled,
        ]);

        // Reset double for this player
        $this->playerDoubles[$playerId] = false;

        $this->currentRound->refresh();
        $this->game->refresh();
        $this->broadcastState();
    }

    public function startRevealing(): void
    {
        if (!$this->currentRound || !$this->allAnswersCollected()) return;

        $this->currentRound->update(['status' => 'revealing', 'current_slide' => 0]);
        $this->revealedCount = 0;
        $this->broadcastState();
    }

    public function revealNext(): void
    {
        if (!$this->currentRound) return;

        $maxAnswers = $this->currentRound->category->answers->count();

        if ($this->revealedCount < $maxAnswers) {
            $this->revealedCount++;
            $this->currentRound->update(['current_slide' => $this->revealedCount]);

            // Update status based on position
            if ($this->revealedCount > 10) {
                $this->currentRound->update(['status' => 'tension']);
            }

            $this->broadcastState();
        }
    }

    public function revealAll(): void
    {
        if (!$this->currentRound) return;

        $maxAnswers = $this->currentRound->category->answers->count();
        $this->revealedCount = $maxAnswers;
        $this->currentRound->update([
            'current_slide' => $maxAnswers,
            'status' => $maxAnswers > 10 ? 'tension' : 'revealing'
        ]);
        $this->broadcastState();
    }

    public function showScores(): void
    {
        if (!$this->currentRound) return;

        // Calculate and apply scores
        foreach ($this->currentRound->playerAnswers as $pa) {
            $player = $pa->player;
            // Score is already calculated, just need to add to total if not already done
            // Actually scores should be added when we move to scoring
        }

        // Apply all scores now
        foreach ($this->currentRound->playerAnswers as $pa) {
            $player = Player::find($pa->player_id);
            if ($player) {
                // Recalculate total from all rounds
                $total = PlayerAnswer::where('player_id', $player->id)
                    ->whereHas('round', fn($q) => $q->where('game_id', $this->game->id))
                    ->sum('points_awarded');
                $player->update(['total_score' => $total]);
            }
        }

        $this->currentRound->update(['status' => 'scoring']);
        $this->game->refresh();
        $this->broadcastState();
    }

    public function correctAnswer(string $playerId, string $answerId): void
    {
        if (!$this->currentRound) return;

        $playerAnswer = PlayerAnswer::where('round_id', $this->currentRound->id)
            ->where('player_id', $playerId)
            ->first();

        if (!$playerAnswer) return;

        $newAnswer = $this->currentRound->category->answers->find($answerId);
        if (!$newAnswer) return;

        $oldPoints = $playerAnswer->points_awarded;
        $newPoints = $newAnswer->points;

        // Apply double if it was doubled
        if ($playerAnswer->was_doubled) {
            $newPoints *= 2;
        }

        $playerAnswer->update([
            'answer_id' => $newAnswer->id,
            'points_awarded' => $newPoints,
        ]);

        // Recalculate player's total score
        $player = Player::find($playerId);
        if ($player) {
            $total = PlayerAnswer::where('player_id', $player->id)
                ->whereHas('round', fn($q) => $q->where('game_id', $this->game->id))
                ->sum('points_awarded');
            $player->update(['total_score' => $total]);
        }

        $this->currentRound->refresh();
        $this->game->refresh();
        $this->broadcastState();
    }

    public function nextRound(): void
    {
        if (!$this->currentRound) return;

        $this->currentRound->update(['status' => 'complete']);

        $nextRoundNum = $this->game->current_round + 1;

        if ($nextRoundNum > $this->game->total_rounds) {
            $this->game->update(['status' => 'completed']);

            // Mark all categories used in this game as played
            $categoryIds = $this->game->rounds()->pluck('category_id');
            Category::whereIn('id', $categoryIds)->whereNull('played_at')->update(['played_at' => now()]);

            $this->broadcastState();
            return;
        }

        $this->game->update(['current_round' => $nextRoundNum]);

        $nextRound = $this->game->rounds()->where('round_number', $nextRoundNum)->first();
        if ($nextRound) {
            $nextRound->update(['status' => 'intro', 'current_slide' => 0]);
        }

        $this->game->refresh();
        $this->loadCurrentState();
        $this->broadcastState();
    }

    public function broadcastState(): void
    {
        // Get revealed answers data
        $revealedAnswersData = [];
        if ($this->currentRound) {
            $answers = $this->currentRound->category->answers->sortBy('position');
            foreach ($answers as $answer) {
                if ($answer->position <= $this->revealedCount) {
                    // Find players who had this answer
                    $playersWithAnswer = $this->currentRound->playerAnswers
                        ->where('answer_id', $answer->id)
                        ->map(fn($pa) => [
                            'id' => $pa->player_id,
                            'name' => $pa->player->name,
                            'color' => $pa->player->color,
                            'doubled' => $pa->was_doubled,
                        ])
                        ->values()
                        ->toArray();

                    $revealedAnswersData[] = [
                        'position' => $answer->position,
                        'text' => $answer->display_text, // Use display_text to hide geographic identifiers
                        'stat' => $answer->stat,
                        'points' => $answer->points,
                        'is_tension' => $answer->is_tension,
                        'players' => $playersWithAnswer,
                    ];
                }
            }
        }

        // Get collected player answers for display during collecting phase
        $collectedAnswers = [];
        if ($this->currentRound) {
            foreach ($this->currentRound->playerAnswers as $pa) {
                $collectedAnswers[] = [
                    'playerId' => $pa->player_id,
                    'playerName' => $pa->player->name,
                    'playerColor' => $pa->player->color,
                    'answerText' => $pa->answer?->text ?? $this->playerAnswers[$pa->player_id] ?? 'Not on list',
                    'isOnList' => $pa->answer_id !== null,
                ];
            }
        }

        // Build state as single object for JavaScript
        $state = [
            'gameId' => $this->game->id,
            'showRules' => $this->showRules,
            'currentRound' => $this->game->current_round,
            'roundStatus' => $this->currentRound?->status,
            'currentSlide' => $this->revealedCount,
            'gameStatus' => $this->game->status,
            'revealedAnswers' => $revealedAnswersData,
            'collectedAnswers' => $collectedAnswers,
            'players' => $this->game->players->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'color' => $p->color,
                'total_score' => $p->total_score,
                'double_used' => $p->double_used,
            ])->toArray(),
        ];

        $this->dispatch('game-state-updated', state: $state);
    }
}; ?>

<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold">{{ $game->name }}</h1>
                <p class="text-slate-400">
                    Round {{ $game->current_round }} of {{ $game->total_rounds }}
                    @if($currentRound)
                        - {{ $currentRound->category->title }}
                    @endif
                </p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('games.present', $game) }}" target="tension-presentation"
                   class="bg-purple-600 hover:bg-purple-700 px-4 py-2 rounded-lg font-medium transition">
                    Open Presentation
                </a>
                <a href="{{ route('games.show', $game) }}"
                   class="bg-slate-700 hover:bg-slate-600 px-4 py-2 rounded-lg font-medium transition">
                    Back to Setup
                </a>
            </div>
        </div>

        @if($game->status === 'completed')
            <!-- Game Complete -->
            <div class="bg-slate-800 rounded-xl p-8 text-center">
                <h2 class="text-4xl font-bold mb-4">Game Complete!</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-3xl mx-auto">
                    @foreach($players->sortByDesc('total_score') as $index => $player)
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
        @else
            <div class="grid lg:grid-cols-3 gap-6">
                <!-- Main Control Panel -->
                <div class="lg:col-span-2 space-y-6">

                    {{-- RULES PHASE --}}
                    @if($showRules)
                        <div class="bg-slate-800 rounded-xl p-6 border border-slate-700">
                            <div class="text-center">
                                <h2 class="text-2xl font-bold mb-4">Showing Game Rules</h2>
                                <p class="text-slate-400 mb-6">Players can see the rules on the presentation screen</p>
                                <button wire:click="dismissRules"
                                        class="bg-green-600 hover:bg-green-700 px-8 py-3 rounded-lg font-bold transition">
                                    Continue to Round 1 →
                                </button>
                            </div>
                        </div>

                    {{-- INTRO PHASE --}}
                    @elseif($currentRound?->status === 'intro')
                        <div class="bg-slate-800 rounded-xl p-6 border border-slate-700">
                            <div class="text-center">
                                <p class="text-slate-400 mb-2">Round {{ $game->current_round }}</p>
                                <h2 class="text-3xl font-bold mb-2">{{ $currentRound->category->title }}</h2>
                                @if($currentRound->category->description)
                                    <p class="text-slate-400 mb-6">{{ $currentRound->category->description }}</p>
                                @endif
                                <button wire:click="startCollecting"
                                        class="bg-blue-600 hover:bg-blue-700 px-8 py-3 rounded-lg font-bold transition">
                                    Start Collecting Answers →
                                </button>
                            </div>
                        </div>

                    {{-- COLLECTING PHASE --}}
                    @elseif($currentRound?->status === 'collecting')
                        <div class="bg-slate-800 rounded-xl p-6 border border-slate-700">
                            <div class="flex justify-between items-center mb-6">
                                <h2 class="text-xl font-bold">Collect Player Answers</h2>
                                <span class="text-sm px-3 py-1 rounded bg-blue-600/20 text-blue-400">
                                    {{ $currentRound->category->title }}
                                </span>
                            </div>

                            <div class="space-y-4">
                                @foreach($players->sortBy('position') as $player)
                                    @php
                                        $hasAnswer = !empty($playerAnswers[$player->id] ?? '');
                                        $existingAnswer = $playerAnswerMap[$player->id] ?? null;
                                    @endphp
                                    <div class="bg-slate-900 rounded-lg p-4 {{ $hasAnswer ? 'ring-1 ring-green-500/50' : '' }}">
                                        <div class="flex items-center gap-4">
                                            <div class="w-4 h-4 rounded-full flex-shrink-0" style="background-color: {{ $player->color }}"></div>
                                            <div class="font-bold text-lg flex-shrink-0 w-32" style="color: {{ $player->color }}">
                                                {{ $player->name }}
                                            </div>

                                            <div class="flex-1 flex items-center gap-2" x-data="{ open: false }">
                                                <div class="relative flex-1">
                                                    <input type="text"
                                                           wire:model="playerAnswers.{{ $player->id }}"
                                                           @focus="open = true"
                                                           @blur="setTimeout(() => open = false, 200)"
                                                           @keydown.enter="open = false; $wire.submitPlayerAnswer('{{ $player->id }}')"
                                                           placeholder="Type answer..."
                                                           class="w-full bg-slate-800 border border-slate-600 rounded-lg px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">

                                                    <!-- Autocomplete dropdown -->
                                                    <div x-show="open"
                                                         x-cloak
                                                         class="absolute z-10 w-full mt-1 bg-slate-800 border border-slate-600 rounded-lg shadow-lg max-h-48 overflow-auto"
                                                         x-data="{ input: '' }"
                                                         x-effect="input = ($wire.playerAnswers['{{ $player->id }}'] || '').toLowerCase()">
                                                        @foreach($answers as $answer)
                                                            <button type="button"
                                                                    x-show="input.length > 0 && '{{ strtolower(addslashes($answer->display_text)) }}'.includes(input)"
                                                                    @click="$wire.set('playerAnswers.{{ $player->id }}', '{{ addslashes($answer->display_text) }}'); open = false; $wire.submitPlayerAnswer('{{ $player->id }}')"
                                                                    class="w-full text-left px-4 py-2 hover:bg-slate-700 transition {{ $answer->is_tension ? 'text-red-400' : '' }}">
                                                                <span class="text-slate-500 mr-2">#{{ $answer->position }}</span>
                                                                {{ $answer->display_text }}
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                </div>

                                                @if(!$player->double_used)
                                                    <label class="flex items-center gap-1 cursor-pointer flex-shrink-0">
                                                        <input type="checkbox" wire:model.live="playerDoubles.{{ $player->id }}" class="w-4 h-4 rounded">
                                                        <span class="text-yellow-400 text-sm">2x</span>
                                                    </label>
                                                @endif

                                                <button wire:click="submitPlayerAnswer('{{ $player->id }}')"
                                                        class="bg-slate-700 hover:bg-slate-600 px-3 py-2 rounded transition flex-shrink-0">
                                                    Set
                                                </button>
                                            </div>

                                            @if($existingAnswer)
                                                <div class="flex-shrink-0 text-right">
                                                    <span class="text-green-400">✓</span>
                                                    <span class="text-slate-400 text-sm ml-1">{{ Str::limit($existingAnswer['answer_text'], 20) }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-6 pt-4 border-t border-slate-700 flex justify-between items-center">
                                <div class="text-slate-400">
                                    {{ count(array_filter($playerAnswers)) }} / {{ count($players) }} answers collected
                                </div>
                                <button wire:click="startRevealing"
                                        @disabled(!$allAnswersCollected)
                                        class="bg-green-600 hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed px-8 py-3 rounded-lg font-bold transition">
                                    Start Reveal →
                                </button>
                            </div>
                        </div>

                    {{-- REVEALING / TENSION PHASE --}}
                    @elseif(in_array($currentRound?->status, ['revealing', 'tension']))
                        <div class="bg-slate-800 rounded-xl p-6 border border-slate-700">
                            <div class="flex justify-between items-center mb-6">
                                <h2 class="text-xl font-bold">
                                    @if($revealedCount <= 10)
                                        Revealing Top 10
                                    @else
                                        Revealing TENSION Answers
                                    @endif
                                </h2>
                                <span class="text-sm px-3 py-1 rounded {{ $revealedCount > 10 ? 'bg-red-600/20 text-red-400' : 'bg-green-600/20 text-green-400' }}">
                                    {{ $revealedCount }} / {{ $answers->count() }} revealed
                                </span>
                            </div>

                            <!-- Answers Grid -->
                            <div class="grid grid-cols-2 gap-3 mb-6">
                                @foreach($answers->take(10) as $answer)
                                    @php
                                        $isRevealed = $answer->position <= $revealedCount;
                                        $playersWithThis = collect($playerAnswerMap)->filter(fn($pa) => ($pa['answer']?->id ?? null) === $answer->id);
                                    @endphp
                                    <div class="bg-slate-900 rounded-lg p-3 {{ $isRevealed ? '' : 'opacity-40' }}">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <span class="text-green-400 font-bold">#{{ $answer->position }}</span>
                                                @if($isRevealed)
                                                    <span class="ml-2">{{ $answer->text }}</span>
                                                @else
                                                    <span class="ml-2 text-slate-500">???</span>
                                                @endif
                                            </div>
                                            <span class="text-green-400 font-bold">+{{ $answer->points }}</span>
                                        </div>
                                        @if($isRevealed && $playersWithThis->count() > 0)
                                            <div class="mt-2 flex flex-wrap gap-1">
                                                @foreach($playersWithThis as $playerId => $pa)
                                                    <span class="text-xs px-2 py-0.5 rounded-full" style="background-color: {{ $players->find($playerId)?->color }}30; color: {{ $players->find($playerId)?->color }}">
                                                        {{ $players->find($playerId)?->name }}
                                                        @if($pa['was_doubled']) 2x @endif
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            @if($answers->count() > 10)
                                <h3 class="text-red-400 font-bold mb-3">TENSION ZONE</h3>
                                <div class="grid grid-cols-2 gap-3 mb-6">
                                    @foreach($answers->skip(10) as $answer)
                                        @php
                                            $isRevealed = $answer->position <= $revealedCount;
                                            $playersWithThis = collect($playerAnswerMap)->filter(fn($pa) => ($pa['answer']?->id ?? null) === $answer->id);
                                        @endphp
                                        <div class="bg-red-900/30 border border-red-500/30 rounded-lg p-3 {{ $isRevealed ? '' : 'opacity-40' }}">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <span class="text-red-400 font-bold">#{{ $answer->position }}</span>
                                                    @if($isRevealed)
                                                        <span class="ml-2">{{ $answer->text }}</span>
                                                    @else
                                                        <span class="ml-2 text-slate-500">???</span>
                                                    @endif
                                                </div>
                                                <span class="text-red-400 font-bold">{{ $answer->points }}</span>
                                            </div>
                                            @if($isRevealed && $playersWithThis->count() > 0)
                                                <div class="mt-2 flex flex-wrap gap-1">
                                                    @foreach($playersWithThis as $playerId => $pa)
                                                        <span class="text-xs px-2 py-0.5 rounded-full" style="background-color: {{ $players->find($playerId)?->color }}30; color: {{ $players->find($playerId)?->color }}">
                                                            {{ $players->find($playerId)?->name }}
                                                            @if($pa['was_doubled']) 2x @endif
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            <div class="flex justify-between items-center pt-4 border-t border-slate-700">
                                <button wire:click="revealAll"
                                        class="text-slate-400 hover:text-white transition">
                                    Reveal All
                                </button>
                                <div class="flex gap-3">
                                    @if($revealedCount < $answers->count())
                                        <button wire:click="revealNext"
                                                class="bg-blue-600 hover:bg-blue-700 px-8 py-3 rounded-lg font-bold transition">
                                            Reveal #{{ $revealedCount + 1 }} →
                                        </button>
                                    @else
                                        <button wire:click="showScores"
                                                class="bg-green-600 hover:bg-green-700 px-8 py-3 rounded-lg font-bold transition">
                                            Show Scores →
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>

                    {{-- SCORING PHASE --}}
                    @elseif($currentRound?->status === 'scoring')
                        <div class="bg-slate-800 rounded-xl p-6 border border-slate-700">
                            <h2 class="text-2xl font-bold text-center mb-6">Round {{ $game->current_round }} Complete!</h2>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                                @foreach($players->sortByDesc('total_score') as $index => $player)
                                    @php $pa = $playerAnswerMap[$player->id] ?? null; @endphp
                                    <div class="bg-slate-900 rounded-xl p-4 {{ $index === 0 ? 'ring-2 ring-yellow-500' : '' }}">
                                        <div class="text-lg font-bold" style="color: {{ $player->color }}">
                                            {{ $player->name }}
                                        </div>
                                        <div class="text-3xl font-bold mt-2">{{ $player->total_score }}</div>
                                        @if($pa)
                                            <div class="text-sm text-slate-400 mt-2">
                                                {{ $pa['answer_text'] }}
                                                <span class="{{ $pa['points'] >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                                    ({{ $pa['points'] > 0 ? '+' : '' }}{{ $pa['points'] }}{{ $pa['was_doubled'] ? ' 2x' : '' }})
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            <div class="text-center">
                                <button wire:click="nextRound"
                                        class="bg-green-600 hover:bg-green-700 px-8 py-3 rounded-lg font-bold transition">
                                    @if($game->current_round >= $game->total_rounds)
                                        Finish Game
                                    @else
                                        Next Round →
                                    @endif
                                </button>
                            </div>
                        </div>
                    @endif

                    <!-- Player Answers This Round -->
                    @if($currentRound && count($playerAnswerMap) > 0 && !in_array($currentRound->status, ['intro', 'collecting']))
                        <div class="bg-slate-800 rounded-xl p-6 border border-slate-700">
                            <h3 class="text-lg font-semibold mb-4">Player Answers This Round</h3>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                @foreach($players as $player)
                                    @php $pa = $playerAnswerMap[$player->id] ?? null; @endphp
                                    <div class="bg-slate-900 rounded-lg p-3" x-data="{ editing: false }">
                                        <div class="font-bold text-sm" style="color: {{ $player->color }}">{{ $player->name }}</div>
                                        @if($pa)
                                            <div x-show="!editing">
                                                <div class="text-slate-300 mt-1">{{ $pa['answer_text'] }}</div>
                                                <div class="flex items-center justify-between mt-1">
                                                    <span class="text-xs {{ $pa['points'] >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                                        {{ $pa['points'] > 0 ? '+' : '' }}{{ $pa['points'] }} pts
                                                        @if($pa['was_doubled']) (2x) @endif
                                                    </span>
                                                    <button @click="editing = true" class="text-xs text-blue-400 hover:text-blue-300">Edit</button>
                                                </div>
                                            </div>
                                            <div x-show="editing" x-cloak class="mt-1">
                                                <select @change="$wire.correctAnswer('{{ $player->id }}', $event.target.value); editing = false"
                                                        class="w-full text-xs bg-slate-800 border border-slate-600 rounded px-2 py-1 text-white">
                                                    <option value="">Select correct answer...</option>
                                                    @foreach($answers as $answer)
                                                        <option value="{{ $answer->id }}" {{ $pa['answer']?->id === $answer->id ? 'selected' : '' }}>
                                                            #{{ $answer->position }} {{ $answer->display_text }} ({{ $answer->points > 0 ? '+' : '' }}{{ $answer->points }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button @click="editing = false" class="text-xs text-slate-400 hover:text-slate-300 mt-1">Cancel</button>
                                            </div>
                                        @else
                                            <div class="text-slate-500 mt-1">No answer</div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- GM Scoreboard (always visible to GM) -->
                    <div class="bg-slate-800 rounded-xl p-6 border border-slate-700">
                        <h2 class="text-lg font-semibold mb-4">Scores (GM View)</h2>
                        <div class="space-y-3">
                            @foreach($players->sortByDesc('total_score') as $index => $player)
                                <div class="flex items-center justify-between bg-slate-900 rounded-lg px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="text-slate-500 font-bold w-6">{{ $index + 1 }}</span>
                                        <div class="w-3 h-3 rounded-full" style="background-color: {{ $player->color }}"></div>
                                        <span class="font-medium">{{ $player->name }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xl font-bold">{{ $player->total_score }}</span>
                                        @if(!$player->double_used)
                                            <span class="text-xs bg-yellow-500/20 text-yellow-400 px-1.5 py-0.5 rounded">2x</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Round Progress -->
                    <div class="bg-slate-800 rounded-xl p-6 border border-slate-700">
                        <h3 class="text-sm text-slate-400 mb-2">Round Progress</h3>
                        <div class="flex gap-1">
                            @for($i = 1; $i <= $game->total_rounds; $i++)
                                <div class="flex-1 h-2 rounded-full
                                    @if($i < $game->current_round) bg-green-500
                                    @elseif($i === $game->current_round) bg-blue-500
                                    @else bg-slate-700
                                    @endif
                                "></div>
                            @endfor
                        </div>
                    </div>

                    <!-- Category Reference (GM only - shows full text) -->
                    @if($currentRound && in_array($currentRound->status, ['collecting', 'revealing', 'tension']))
                        <div class="bg-slate-800 rounded-xl p-6 border border-slate-700">
                            <h3 class="text-sm text-slate-400 mb-2">Answer Reference</h3>
                            <div class="space-y-1 text-sm max-h-64 overflow-auto">
                                @foreach($answers as $answer)
                                    <div class="flex justify-between {{ $answer->is_tension ? 'text-red-400' : 'text-slate-300' }}">
                                        <span>#{{ $answer->position }} {{ $answer->text }}</span>
                                        <span>{{ $answer->points > 0 ? '+' : '' }}{{ $answer->points }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <script data-navigate-track>
        (function() {
            const gameId = '{{ $game->id }}';
            const channelName = 'tension-game-' + gameId;

            // Close any existing channel for this game
            if (window.tensionChannel) {
                window.tensionChannel.close();
            }

            const channel = new BroadcastChannel(channelName);
            window.tensionChannel = channel;
            console.log('[Control] BroadcastChannel initialized for game:', gameId);

            // Listen for Livewire 3 dispatched events
            document.addEventListener('game-state-updated', function(event) {
                const state = event.detail.state || event.detail;
                console.log('[Control] Broadcasting state:', state);
                channel.postMessage(state);
            });

            channel.onmessage = function(event) {
                console.log('[Control] Received message:', event.data);
                if (event.data && event.data.type === 'request-state') {
                    console.log('[Control] Presentation requested state, triggering refresh...');
                    // Find the Livewire component and call broadcastState
                    const component = Livewire.all()[0];
                    if (component) {
                        component.$wire.handleBroadcastState();
                    }
                }
            };

            // Broadcast initial state after a short delay
            setTimeout(function() {
                console.log('[Control] Sending initial broadcast');
                const component = Livewire.all()[0];
                if (component) {
                    component.$wire.handleBroadcastState();
                }
            }, 500);
        })();
    </script>
</div>
