<?php

use App\Models\Game;
use App\Models\Round;
use App\Services\GameStateMachine;
use Livewire\Volt\Component;

new class extends Component {
    public Game $game;
    public Round $currentRound;
    public int $revealedCount = 0;

    public function mount(): void
    {
        $this->revealedCount = $this->currentRound->current_slide;
    }

    public function revealNext(): void
    {
        $stateMachine = new GameStateMachine($this->game);
        $this->revealedCount = $stateMachine->revealNext();
        $this->currentRound->refresh();
        $this->dispatch('state-updated');
    }

    public function revealAll(): void
    {
        $stateMachine = new GameStateMachine($this->game);
        $stateMachine->revealAll();
        $this->currentRound->refresh();
        $this->revealedCount = $this->currentRound->current_slide;
        $this->dispatch('state-updated');
    }

    public function showScores(): void
    {
        $this->dispatch('transition-to-scoring');
    }

    public function goBackToCollecting(): void
    {
        $this->dispatch('transition-to-collecting');
    }

    public function with(): array
    {
        $answers = $this->currentRound->category->answers->sortBy('position');
        
        $playerAnswerMap = [];
        foreach ($this->currentRound->playerAnswers as $pa) {
            $playerAnswerMap[$pa->player_id] = [
                'answer' => $pa->answer,
                'was_doubled' => $pa->was_doubled,
            ];
        }

        return [
            'answers' => $answers,
            'playerAnswerMap' => $playerAnswerMap,
            'players' => $this->game->players,
        ];
    }
}; ?>

<div class="space-y-4">
    <div class="bg-slate-900/40 rounded-[2.5rem] p-6 border border-white/5 shadow-2xl backdrop-blur-xl">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
            <div>
                <h2 class="text-2xl font-black text-white tracking-tight uppercase leading-none">Reveal Answers</h2>
                <p class="text-slate-500 font-bold mt-1 uppercase tracking-widest text-[9px]">Phase 2: Board Reveal</p>
            </div>
            
            <div class="flex items-center gap-4 bg-slate-800/20 p-1 rounded-2xl border border-white/5">
                <div class="px-3 py-1">
                    <span class="text-[9px] font-black uppercase tracking-widest text-blue-400">{{ $revealedCount }} / {{ $answers->count() }} Uncovered</span>
                </div>
            </div>
        </div>

        <!-- Board Grid: Optimized for 3 columns to fit vertically -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 mb-6">
            @foreach($answers as $answer)
                @php
                    $isRevealed = $answer->position <= $revealedCount;
                    $isFriction = $answer->position > $game->top_answers_count;
                    $playersWithThis = collect($playerAnswerMap)->filter(fn($pa) => ($pa['answer']?->id ?? null) === $answer->id);
                @endphp
                <div class="relative transition-all duration-500 {{ $isRevealed ? '' : 'grayscale opacity-30 scale-[0.98]' }}">
                    <div class="bg-slate-800/40 border-2 {{ $isRevealed ? ($isFriction ? 'border-red-500/40 shadow-[0_0_15px_rgba(239,68,68,0.1)]' : 'border-green-500/40 shadow-[0_0_15px_rgba(34,197,94,0.1)]') : 'border-slate-700/50' }} rounded-xl p-2 flex items-center justify-between group shadow-inner">
                        <div class="flex items-center gap-2 overflow-hidden">
                            <div class="w-7 h-7 flex-shrink-0 rounded-lg {{ $isFriction ? 'bg-red-500/10 text-red-500' : 'bg-green-500/10 text-green-500' }} flex items-center justify-center font-black text-[10px] border {{ $isFriction ? 'border-red-500/20' : 'border-green-500/20' }}">
                                {{ $answer->position }}
                            </div>
                            <div class="flex flex-col truncate">
                                <span class="text-sm font-black text-white tracking-tight uppercase truncate">
                                    {{ $isRevealed ? $answer->text : '???' }}
                                </span>
                                @if($isRevealed && $playersWithThis->count() > 0)
                                    <div class="flex flex-wrap gap-1 mt-0.5">
                                        @foreach($playersWithThis as $playerId => $pa)
                                            <div class="flex items-center gap-1 px-1 py-0.5 rounded-full bg-white/5 border border-white/10">
                                                <div class="w-1 h-1 rounded-full" style="background-color: {{ $players->find($playerId)?->color }}"></div>
                                                <span class="text-[6px] font-black text-white/70 uppercase">{{ $players->find($playerId)?->name }}</span>
                                                @if($pa['was_doubled'])
                                                    <span class="text-[6px] font-black text-yellow-500">2x</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="flex flex-col items-end">
                            <span class="text-base font-black {{ $isFriction ? 'text-red-500' : 'text-green-500' }} tracking-tighter">
                                {{ $answer->points > 0 ? '+' : '' }}{{ $answer->points }}
                            </span>
                            @if($isRevealed && $answer->stat)
                                <span class="text-[7px] font-black text-slate-500 uppercase">{{ $answer->stat }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex justify-between items-center pt-4 border-t border-slate-800/50">
            <div class="flex items-center gap-6">
                <button wire:click="goBackToCollecting"
                        class="text-slate-600 hover:text-white transition font-black uppercase tracking-[0.2em] text-[9px] flex items-center gap-2">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Back to Inputs
                </button>
                <button wire:click="revealAll"
                        class="text-slate-600 hover:text-white transition font-black uppercase tracking-widest text-[9px] bg-slate-800/40 px-3 py-1.5 rounded-xl border border-white/5">
                    Reveal Board
                </button>
            </div>
            
            <div class="flex gap-4">
                @if($revealedCount < $answers->count())
                    <button wire:click="revealNext"
                            class="bg-blue-600 hover:bg-blue-500 px-6 py-2.5 rounded-xl font-black uppercase tracking-widest transition-all shadow-xl shadow-blue-500/20 border-b-4 border-blue-800 active:translate-y-1 active:border-b-0 text-xs">
                        Reveal #{{ $revealedCount + 1 }} →
                    </button>
                @else
                    <button wire:click="showScores"
                            class="bg-green-600 hover:bg-green-500 px-6 py-2.5 rounded-xl font-black uppercase tracking-widest transition-all shadow-xl shadow-green-500/20 border-b-4 border-green-800 active:translate-y-1 active:border-b-0 text-xs">
                        End Reveal →
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
