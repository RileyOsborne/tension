<?php

use App\Models\Game;
use Livewire\Volt\Component;

new class extends Component {
    public Game $game;

    public function dismissRules(): void
    {
        $this->dispatch('rules-dismissed');
    }
}; ?>

<div class="bg-slate-900/40 rounded-[3rem] p-16 border border-white/5 shadow-2xl backdrop-blur-xl text-center flex flex-col items-center justify-center min-h-[400px]">
    <div class="space-y-8 max-w-2xl">
        <div class="space-y-2">
            <p class="text-xs font-black uppercase tracking-[0.4em] text-slate-500">GETTING STARTED</p>
            <h2 class="text-6xl font-black text-white uppercase tracking-tighter leading-none">THE RULES</h2>
        </div>
        
        <p class="text-lg font-bold text-slate-400 uppercase tracking-widest leading-relaxed">
            Players can now see the scoring guide and zone penalties on the presentation screen.
        </p>

        <div class="pt-8">
            <button wire:click="dismissRules"
                    class="bg-green-600 border-b-8 border-green-800 hover:bg-green-500 text-white px-12 py-6 rounded-[2rem] text-2xl font-black uppercase tracking-widest transition-all active:translate-y-1 active:border-b-0 shadow-xl shadow-green-600/20">
                Continue to Round 1 →
            </button>
        </div>
    </div>
</div>
