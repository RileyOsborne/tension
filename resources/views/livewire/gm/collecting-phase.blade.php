<?php

use App\Models\Game;
use App\Models\Round;
use App\Models\Player;
use App\Services\GameStateMachine;
use Livewire\Volt\Component;

new class extends Component {
    public Game $game;
    public Round $currentRound;

    public array $playerAnswers = [];
    public array $playerDoubles = [];

    public function mount(): void
    {
        $this->loadPlayerState();
    }

    public function loadPlayerState(): void
    {
        $this->currentRound->load(['playerAnswers.player', 'category.answers']);
        
        foreach ($this->game->players->filter(fn($p) => $p->isActive()) as $player) {
            $existing = $this->currentRound->playerAnswers->where('player_id', $player->id)->first();
            if ($existing) {
                $this->playerAnswers[$player->id] = $existing->answer?->display_text 
                    ?? $existing->answer?->text 
                    ?? $existing->input_text;
            } else {
                $this->playerAnswers[$player->id] = '';
            }
            $this->playerDoubles[$player->id] = $existing?->was_doubled ?? false;
        }
    }

    public function submitPlayerAnswer(string $playerId): void
    {
        $answerText = trim($this->playerAnswers[$playerId] ?? '');
        if (empty($answerText)) return;

        $stateMachine = new GameStateMachine($this->game);
        $stateMachine->submitPlayerAnswer(
            $playerId,
            $answerText,
            $this->playerDoubles[$playerId] ?? false
        );

        $this->loadPlayerState();
        $this->dispatch('player-answer-updated');
    }

    public function startRevealing(): void
    {
        if (!$this->allAnswersCollected()) return;
        $this->dispatch('transition-to-reveal');
    }

    public function goBackToIntro(): void
    {
        $this->dispatch('transition-to-intro');
    }

    public function allAnswersCollected(): bool
    {
        $activePlayers = $this->game->players->filter(fn($p) => $p->isActive());
        foreach ($activePlayers as $player) {
            $hasAnswer = $this->currentRound->playerAnswers->where('player_id', $player->id)->isNotEmpty();
            if (!$hasAnswer && empty($this->playerAnswers[$player->id] ?? '')) {
                return false;
            }
        }
        return true;
    }

    public function confirmRemovePlayer(string $playerId): void
    {
        $this->dispatch('confirm-remove-player', playerId: $playerId);
    }

    public function with(): array
    {
        $answers = $this->currentRound->category->answers->sortBy('position');
        
        $playerAnswerMap = [];
        foreach ($this->currentRound->playerAnswers as $pa) {
            $playerAnswerMap[$pa->player_id] = [
                'answer_text' => $pa->answer?->display_text ?? $pa->answer?->text ?? $pa->input_text,
                'is_on_list' => $pa->answer_id !== null,
            ];
        }

        $turnOrder = collect($this->game->getTurnOrderForRound($this->game->current_round))
            ->filter(fn($p) => $p->isActive());

        $stateMachine = new GameStateMachine($this->game);
        $turnInfo = $stateMachine->getCurrentTurnInfo();

        return [
            'turnOrder' => $turnOrder,
            'playerAnswerMap' => $playerAnswerMap,
            'answerOptions' => $answers,
            'currentPlayer' => $turnInfo['currentPlayer'],
            'timerMode' => $turnInfo['timerMode'],
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="bg-slate-900/40 rounded-[2.5rem] p-6 border border-white/5 shadow-2xl">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
            <div>
                <h2 class="text-3xl font-black text-white tracking-tight uppercase">Collect Answers</h2>
                <p class="text-slate-500 font-bold mt-1 uppercase tracking-widest text-[10px]">Phase 1: Input & Verification</p>
            </div>
            
            <div class="flex items-center gap-4 bg-slate-800/20 p-1.5 rounded-2xl border border-white/5">
                @if($currentPlayer)
                    <div class="flex items-center gap-3 px-4 py-2 bg-blue-600/10 rounded-xl border border-blue-500/20">
                        <div class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></div>
                        <span class="text-[10px] font-black uppercase tracking-widest text-blue-400">Turn: {{ $currentPlayer->name }}</span>
                    </div>
                @endif
                <div class="px-4 py-2">
                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">{{ $currentRound->playerAnswers->count() }} / {{ $turnOrder->count() }} Submitted</span>
                </div>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            @foreach($turnOrder as $player)
                @php
                    $existingAnswer = $playerAnswerMap[$player->id] ?? null;
                    $isCurrent = $currentPlayer && $player->id === $currentPlayer->id;
                    $isDisconnected = $player->isGmControlled() && !$player->isGmCreated();
                @endphp
                <div class="relative group">
                    <!-- Status Glow -->
                    @if($existingAnswer)
                        <div class="absolute -inset-0.5 bg-green-500/10 rounded-[2rem] blur opacity-50"></div>
                    @elseif($isCurrent)
                        <div class="absolute -inset-0.5 bg-blue-500/10 rounded-[2rem] blur opacity-50"></div>
                    @endif

                    <div class="relative bg-slate-800/40 border-2 {{ $existingAnswer ? 'border-green-500/20' : ($isCurrent ? 'border-blue-500/40 shadow-[0_0_15px_rgba(59,130,246,0.1)]' : 'border-white/5') }} rounded-[2rem] p-4 transition-all duration-300">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-3">
                                <div class="w-2.5 h-2.5 rounded-full shadow-lg" style="background-color: {{ $player->color }}"></div>
                                <span class="font-black text-white tracking-tight uppercase text-sm">{{ $player->name }}</span>
                                @if($isDisconnected)
                                    <span class="text-[8px] font-black text-yellow-500/50 border border-yellow-500/20 px-1.5 py-0.5 rounded-full uppercase tracking-tighter">DC</span>
                                @endif
                            </div>
                            @if($existingAnswer)
                                <div class="flex items-center gap-2">
                                    <span class="text-[8px] font-black uppercase tracking-widest {{ $existingAnswer['is_on_list'] ? 'text-green-500' : 'text-red-500' }}">
                                        {{ $existingAnswer['is_on_list'] ? 'On List' : 'Off List' }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        <div class="flex items-center gap-2" x-data="{ open: false }">
                            <div class="relative flex-1">
                                <input type="text"
                                       wire:model="playerAnswers.{{ $player->id }}"
                                       @focus="open = true"
                                       @blur="setTimeout(() => open = false, 200)"
                                       @keydown.enter="open = false; $wire.submitPlayerAnswer('{{ $player->id }}')"
                                       placeholder="Enter answer..."
                                       class="w-full bg-slate-700/20 border-0 border-b-2 border-slate-700 focus:border-blue-500 focus:ring-0 px-3 py-1.5 text-base font-bold text-white placeholder-slate-600 transition rounded-t-xl">

                                <!-- Autocomplete -->
                                <div x-show="open" x-cloak
                                     class="absolute z-50 w-full mt-2 bg-slate-800 border border-slate-700 rounded-2xl shadow-2xl max-h-48 overflow-auto backdrop-blur-xl"
                                     x-data="{ input: '' }"
                                     x-effect="input = ($wire.playerAnswers['{{ $player->id }}'] || '').toLowerCase()">
                                    @foreach($answerOptions as $answer)
                                        <button type="button"
                                                x-show="input.length > 0 && '{{ strtolower(addslashes($answer->display_text)) }}'.includes(input)"
                                                @click="$wire.set('playerAnswers.{{ $player->id }}', '{{ addslashes($answer->display_text) }}'); open = false; $wire.submitPlayerAnswer('{{ $player->id }}')"
                                                class="w-full text-left px-5 py-3 hover:bg-blue-600/10 transition border-b border-slate-700 last:border-0 flex justify-between items-center group/item">
                                            <div class="flex items-center gap-3">
                                                <span class="text-[10px] font-black text-slate-500 group-hover/item:text-blue-400">#{{ $answer->position }}</span>
                                                <span class="font-bold text-sm text-slate-300 group-hover/item:text-white">{{ $answer->display_text }}</span>
                                            </div>
                                            <span class="text-[10px] font-black {{ $answer->is_friction ? 'text-red-500' : 'text-green-500' }}">
                                                {{ $answer->points > 0 ? '+' : '' }}{{ $answer->points }}
                                            </span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            @if($player->canUseDouble())
                                <button @click="$wire.set('playerDoubles.{{ $player->id }}', !$wire.playerDoubles['{{ $player->id }}'])"
                                        class="w-10 h-10 rounded-xl flex items-center justify-center transition border-2
                                               {{ $playerDoubles[$player->id] ? 'bg-yellow-500 border-yellow-400 text-black shadow-lg shadow-yellow-500/20' : 'bg-slate-700/40 border-slate-700 text-slate-500 hover:border-slate-600' }}">
                                    <span class="font-black text-xs">2x</span>
                                </button>
                            @endif

                            <button wire:click="submitPlayerAnswer('{{ $player->id }}')"
                                    class="bg-slate-700/60 hover:bg-slate-700 text-white px-4 py-2.5 rounded-xl font-black text-xs uppercase tracking-widest transition border border-slate-600">
                                Set
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-12 flex justify-between items-center pt-8 border-t border-slate-800/50">
            <button wire:click="goBackToIntro"
                    class="text-slate-600 hover:text-white transition font-black uppercase tracking-[0.2em] text-[10px] flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Intro
            </button>
            
            <button wire:click="startRevealing"
                    @disabled(!$this->allAnswersCollected())
                    class="bg-green-600 hover:bg-green-500 disabled:opacity-30 disabled:grayscale px-10 py-4 rounded-2xl font-black uppercase tracking-widest transition-all shadow-xl shadow-green-500/20 border-b-4 border-green-800 active:translate-y-1 active:border-b-0">
                Start Reveal →
            </button>
        </div>
    </div>
</div>
