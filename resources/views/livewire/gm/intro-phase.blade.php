<?php

use App\Models\Game;
use App\Models\Round;
use Livewire\Volt\Component;

new class extends Component {
    public Game $game;
    public Round $currentRound;

    public function startCollecting(): void
    {
        $this->dispatch('transition-to-collecting');
    }
}; ?>

<div class="bg-slate-900/40 rounded-[3rem] p-16 border border-white/5 shadow-2xl backdrop-blur-xl text-center flex flex-col items-center justify-center min-h-[400px]">
    <div class="space-y-6 max-w-2xl">
        <div class="space-y-2">
            <p class="text-xs font-black uppercase tracking-[0.4em] text-slate-500">ROUND {{ $game->current_round }} PREVIEW</p>
            <h2 class="text-6xl font-black text-white uppercase tracking-tighter leading-none">{{ $currentRound->category->title }}</h2>
        </div>
        
        @if($currentRound->category->description)
            <p class="text-xl font-bold text-slate-400 uppercase tracking-tight">{{ $currentRound->category->description }}</p>
        @endif

        <div class="pt-10">
            <button wire:click="startCollecting"
                    class="bg-blue-600 border-b-8 border-blue-800 hover:bg-blue-500 text-white px-12 py-6 rounded-[2rem] text-2xl font-black uppercase tracking-widest transition-all active:translate-y-1 active:border-b-0 shadow-xl shadow-blue-600/20">
                Start Answer Collection →
            </button>
        </div>
    </div>
</div>
