<?php

use App\Models\Game;
use App\Models\Round;
use App\Models\Player;
use App\Models\PlayerAnswer;
use App\Services\GameStateMachine;
use Livewire\Volt\Component;

new class extends Component {
    public Game $game;
    public Round $currentRound;

    public function nextRound(): void
    {
        $this->dispatch('transition-to-next-round');
    }

    public function goBackToRevealing(): void
    {
        $this->dispatch('transition-to-revealing');
    }

    public function correctAnswer(string $playerId, string $answerId): void
    {
        $playerAnswer = PlayerAnswer::where('round_id', $this->currentRound->id)
            ->where('player_id', $playerId)
            ->first();

        if (!$playerAnswer) return;

        $newAnswer = $this->currentRound->category->answers->find($answerId);
        if (!$newAnswer) return;

        $newPoints = $newAnswer->points;
        if ($playerAnswer->was_doubled) {
            $newPoints *= $this->game->double_multiplier;
        }

        $playerAnswer->update([
            'answer_id' => $newAnswer->id,
            'points_awarded' => $newPoints,
        ]);

        $stateMachine = new GameStateMachine($this->game);
        $stateMachine->refresh();
        $this->dispatch('state-updated');
    }

    public function with(): array
    {
        $players = $this->game->players->filter(fn($p) => $p->isActive());
        $answers = $this->currentRound->category->answers->sortBy('position');
        
        $playerAnswerMap = [];
        foreach ($this->currentRound->playerAnswers as $pa) {
            $playerAnswerMap[$pa->player_id] = [
                'answer' => $pa->answer,
                'answer_text' => $pa->answer?->display_text ?? $pa->answer?->text ?? $pa->input_text,
                'points' => $pa->points_awarded,
                'was_doubled' => $pa->was_doubled,
            ];
        }

        return [
            'players' => $players,
            'answers' => $answers,
            'playerAnswerMap' => $playerAnswerMap,
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="bg-slate-900/40 rounded-[2.5rem] p-8 border border-white/5 shadow-2xl backdrop-blur-xl">
        <h2 class="text-3xl font-black text-white text-center mb-8 uppercase tracking-tighter leading-none">ROUND COMPLETE!</h2>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            @foreach($players->sortByDesc('total_score') as $index => $player)
                @php $pa = $playerAnswerMap[$player->id] ?? null; @endphp
                <div class="bg-slate-800/40 rounded-3xl p-5 border-2 {{ $index === 0 ? 'border-yellow-500 shadow-[0_0_25px_rgba(234,179,8,0.15)]' : 'border-slate-700/50' }} transition hover:border-slate-600 shadow-inner">
                    <div class="text-base font-black uppercase tracking-tight" style="color: {{ $player->color }}">
                        {{ $player->name }}
                    </div>
                    <div class="text-3xl font-black text-white mt-1 tracking-tighter">{{ $player->total_score }}</div>
                    @if($pa)
                        <div class="text-[9px] font-black uppercase tracking-widest text-slate-500 mt-3 leading-tight">
                            {{ $pa['answer_text'] }}
                            <div class="mt-0.5 {{ $pa['points'] >= 0 ? 'text-green-500' : 'text-red-500' }}">
                                {{ $pa['points'] > 0 ? '+' : '' }}{{ $pa['points'] }}{{ $pa['was_doubled'] ? ' 2X' : '' }} PTS
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="flex justify-between items-center pt-6 border-t border-white/5">
            <button wire:click="goBackToRevealing"
                    class="text-slate-600 hover:text-white transition font-black uppercase tracking-[0.2em] text-[10px] flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Reveal
            </button>
            <button wire:click="nextRound"
                    class="bg-green-600 border-b-8 border-green-800 hover:bg-green-500 text-white px-10 py-4 rounded-2xl font-black uppercase tracking-widest transition-all active:translate-y-1 active:border-b-0 shadow-xl shadow-green-600/20 text-sm">
                @if($game->current_round >= $game->total_rounds)
                    Finish Game
                @else
                    Next Round →
                @endif
            </button>
        </div>
    </div>

    <!-- Corrections Card -->
    <div class="bg-slate-900/40 rounded-[2.5rem] p-8 border border-white/5 shadow-2xl backdrop-blur-xl">
        <h3 class="text-[10px] font-black uppercase tracking-[0.3em] text-slate-500 mb-6">Review Submissions</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
            @foreach($players as $player)
                @php $pa = $playerAnswerMap[$player->id] ?? null; @endphp
                <div class="bg-slate-800/40 rounded-2xl p-4 border border-slate-700/50 shadow-inner group" x-data="{ editing: false }">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-2 h-2 rounded-full" style="background-color: {{ $player->color }}"></div>
                        <span class="text-[10px] font-black uppercase text-white">{{ $player->name }}</span>
                    </div>
                    
                    @if($pa)
                        <div x-show="!editing">
                            <div class="text-xs font-bold text-slate-300 mb-2 truncate">{{ $pa['answer_text'] }}</div>
                            <div class="flex items-center justify-between">
                                <span class="text-[9px] font-black {{ $pa['points'] >= 0 ? 'text-green-500' : 'text-red-500' }} uppercase">
                                    {{ $pa['points'] }} PTS @if($pa['was_doubled']) (2X) @endif
                                </span>
                                <button @click="editing = true" class="text-[9px] font-black uppercase text-blue-500 hover:text-blue-400">Correct</button>
                            </div>
                        </div>
                        <div x-show="editing" x-cloak class="space-y-2">
                            <select @change="$wire.correctAnswer('{{ $player->id }}', $event.target.value); editing = false"
                                    class="w-full bg-slate-900 border-2 border-slate-700 rounded-xl px-2 py-1.5 text-[9px] font-black text-white focus:border-blue-500 appearance-none uppercase">
                                <option value="">MATCH TO...</option>
                                @foreach($answers as $answer)
                                    <option value="{{ $answer->id }}" {{ ($pa['answer']?->id ?? null) === $answer->id ? 'selected' : '' }}>
                                        #{{ $answer->position }} {{ $answer->display_text }}
                                    </option>
                                @endforeach
                            </select>
                            <button @click="editing = false" class="w-full text-[8px] font-black uppercase text-slate-600 hover:text-slate-400 transition">Cancel</button>
                        </div>
                    @else
                        <div class="text-[9px] font-black uppercase text-slate-700 italic">No Answer Submitted</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
