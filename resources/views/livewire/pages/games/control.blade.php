<?php

use App\Models\Game;
use App\Models\Player;
use App\Models\Round;
use App\Services\GameStateMachine;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\On;

new #[Layout('components.layouts.app')] #[Title('Game Master Control')] class extends Component {
    public Game $game;
    public ?Round $currentRound = null;
    protected ?GameStateMachine $stateMachine = null;

    public ?string $confirmingRemovePlayerId = null;

    protected function getStateMachine(): GameStateMachine
    {
        if (!$this->stateMachine) {
            $this->stateMachine = new GameStateMachine($this->game);
        }
        return $this->stateMachine;
    }

    #[On('broadcast-state')]
    public function handleBroadcastState(): void
    {
        $state = $this->getStateMachine()->broadcast();
        $this->dispatch('game-state-updated', state: $state);
    }

    #[On('echo:game.{game.id},player.answer.submitted')]
    public function handlePlayerAnswer(array $data): void
    {
        $this->refreshAll();
    }

    #[On('echo:game.{game.id},player.joined')]
    public function handlePlayerJoined(array $data): void
    {
        $this->refreshAll();
    }

    #[On('echo:game.{game.id},player.left')]
    public function handlePlayerLeft(array $data): void
    {
        $this->refreshAll();
    }

    #[On('player-answer-updated')]
    #[On('state-updated')]
    public function refreshAll(): void
    {
        $this->game->refresh();
        $this->game->load(['players', 'rounds']);
        $this->getStateMachine()->refresh();
        $this->loadCurrentState();
        $this->broadcastState();
    }

    public function mount(Game $game): void
    {
        abort_unless($game->user_id === auth()->id(), 403);
        $this->game = $game->load(['players', 'rounds.category.answers']);
        $this->loadCurrentState();
    }

    public function loadCurrentState(): void
    {
        $this->currentRound = $this->game->rounds()
            ->where('round_number', $this->game->current_round)
            ->with(['category.answers', 'playerAnswers.player', 'playerAnswers.answer'])
            ->first();
    }

    #[On('rules-dismissed')]
    public function dismissRules(): void
    {
        $this->getStateMachine()->dismissRules();
        $this->refreshAll();
    }

    #[On('transition-to-collecting')]
    public function startCollecting(): void
    {
        $this->getStateMachine()->startCollecting();
        $this->refreshAll();
    }

    #[On('transition-to-reveal')]
    public function startRevealing(): void
    {
        $this->getStateMachine()->startRevealing();
        $this->refreshAll();
    }

    #[On('transition-to-scoring')]
    public function showScores(): void
    {
        $this->getStateMachine()->showScores();
        $this->refreshAll();
    }

    #[On('transition-to-next-round')]
    public function nextRound(): void
    {
        $hasMore = $this->getStateMachine()->nextRound();
        if ($hasMore) {
            $this->loadCurrentState();
        }
        $this->refreshAll();
    }

    #[On('transition-to-intro')]
    public function goBackToIntro(): void
    {
        $this->getStateMachine()->goBackToIntro();
        $this->refreshAll();
    }

    #[On('transition-to-revealing')]
    public function goBackToRevealing(): void
    {
        $this->getStateMachine()->goBackToRevealing();
        $this->refreshAll();
    }

    public function returnToSetup(): void
    {
        $this->getStateMachine()->returnToSetup();
        $this->redirect(route('games.show', $this->game), navigate: true);
    }

    public function broadcastState(): void
    {
        $state = $this->getStateMachine()->broadcast();
        $this->dispatch('game-state-updated', state: $state);
    }

    public function confirmRemovePlayer(string $playerId): void
    {
        $this->confirmingRemovePlayerId = $playerId;
    }

    #[On('confirm-remove-player')]
    public function handleConfirmRemovePlayer($playerId): void
    {
        $this->confirmingRemovePlayerId = $playerId;
    }

    public function cancelRemovePlayer(): void
    {
        $this->confirmingRemovePlayerId = null;
    }

    public function removePlayer(?string $playerId = null): void
    {
        $playerId = $playerId ?? $this->confirmingRemovePlayerId;
        $this->confirmingRemovePlayerId = null;

        $player = $this->game->players()->find($playerId);
        if ($player) {
            $player->update(['removed_at' => now()]);
            $this->refreshAll();
        }
    }

    public function restorePlayer(string $playerId): void
    {
        $player = $this->game->players()->find($playerId);
        if ($player) {
            $player->update(['removed_at' => null]);
            $this->refreshAll();
        }
    }

    public function with(): array
    {
        return [
            'activePlayers' => $this->game->players->filter(fn($p) => $p->isActive())->sortByDesc('total_score')->values(),
            'removedPlayers' => $this->game->players->filter(fn($p) => $p->isRemoved()),
            'answers' => $this->currentRound?->category->answers->sortBy('position') ?? collect(),
        ];
    }
}; ?>

<div class="min-h-screen bg-slate-950 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white antialiased flex flex-col" wire:poll.10s="refreshAll">
    <!-- Clean, minimal top nav -->
    <div class="px-8 py-4 flex items-center justify-between sticky top-0 z-40 bg-slate-900/50 backdrop-blur-md border-b border-white/5">
        <div class="flex items-center gap-6">
            <span class="text-2xl font-title tracking-tighter opacity-80">
                <span class="inline-flex items-baseline"><span class="text-white">FRIC</span><span class="text-red-500 ml-[0.04em]">TION</span></span>
            </span>
            <span class="w-px h-6 bg-slate-700"></span>
            <h1 class="text-xl font-black text-white tracking-tighter uppercase leading-none flex items-center gap-3">
                <span class="text-slate-500">GM</span> {{ $game->name }}
            </h1>
            
            <div class="ml-6 flex items-center gap-1.5">
                @for($i = 1; $i <= $game->total_rounds; $i++)
                    <div class="w-4 h-1.5 rounded-full {{ $i < $game->current_round ? 'bg-green-500' : ($i === $game->current_round ? 'bg-blue-500 shadow-[0_0_10px_rgba(59,130,246,0.5)]' : 'bg-slate-800') }}"></div>
                @endfor
            </div>
            <span class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-2">ROUND {{ $game->current_round }} OF {{ $game->total_rounds }}</span>
        </div>

        <div class="flex items-center gap-4">
            @if($game->join_code)
                <div class="bg-slate-800/40 border border-white/5 rounded-xl px-4 py-2 flex items-center gap-3 shadow-inner">
                    <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">CODE</span>
                    <span class="font-mono text-xl font-black text-blue-400 tracking-widest">{{ $game->join_code }}</span>
                </div>
            @endif
            <a href="{{ route('games.present', $game) }}" target="friction-presentation"
               class="bg-purple-600 border-b-4 border-purple-800 hover:bg-purple-700 text-white px-6 py-2.5 rounded-xl font-black uppercase tracking-widest text-xs transition-all active:translate-y-1 active:border-b-0">
                Presentation
            </a>
            <button wire:click="returnToSetup"
                    class="bg-slate-800 border-b-4 border-slate-900 hover:bg-slate-700 text-slate-300 px-6 py-2.5 rounded-xl font-black uppercase tracking-widest text-xs transition-all active:translate-y-1 active:border-b-0">
                Exit to Setup
            </button>
        </div>
    </div>

    <main class="flex-1 p-4 grid lg:grid-cols-12 gap-4 max-w-[1600px] mx-auto w-full items-start">
        <!-- Main Game Area -->
        <div class="lg:col-span-8 xl:col-span-9 space-y-4 flex flex-col">
            <div class="relative flex-1 min-h-[250px]">
                @if($game->status === 'completed')
                    <livewire:gm.completed-game :game="$game" />
                @else
                    @if($game->show_rules)
                        <livewire:gm.rules-phase :game="$game" />
                    @elseif($currentRound?->status === 'intro')
                        <livewire:gm.intro-phase :game="$game" :currentRound="$currentRound" />
                    @elseif($currentRound?->status === 'collecting')
                        <livewire:gm.collecting-phase :game="$game" :currentRound="$currentRound" :key="'collect-'.$currentRound->id" />
                    @elseif(in_array($currentRound?->status, ['revealing', 'friction']))
                        <livewire:gm.revealing-phase :game="$game" :currentRound="$currentRound" :key="'reveal-'.$currentRound->id" />
                    @elseif($currentRound?->status === 'scoring')
                        <livewire:gm.scoring-phase :game="$game" :currentRound="$currentRound" :key="'score-'.$currentRound->id" />
                    @endif
                @endif
            </div>

            <!-- Horizontal Answer Reference Panel -->
            @if($currentRound && in_array($currentRound->status, ['collecting', 'revealing', 'friction']))
                <div class="bg-slate-900/50 rounded-[2rem] border border-white/5 p-5 backdrop-blur-xl shadow-2xl">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">CATEGORY: {{ $currentRound->category->title }}</h3>
                        <div class="flex gap-4">
                            <span class="text-[9px] font-black text-green-500 uppercase tracking-widest flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> TOP 10
                            </span>
                            <span class="text-[9px] font-black text-red-500 uppercase tracking-widest flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> FRICTION
                            </span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 xl:grid-cols-5 gap-2">
                        @foreach($answers as $answer)
                            <div class="bg-slate-800/40 rounded-xl px-3 py-2 border {{ $answer->is_friction ? 'border-red-900/20' : 'border-slate-700/50' }} group hover:border-slate-600 transition shadow-inner">
                                <div class="flex items-center justify-between mb-0.5">
                                    <span class="text-[9px] font-black text-slate-500 uppercase">#{{ $answer->position }}</span>
                                    <span class="text-[9px] font-black {{ $answer->points > 0 ? 'text-green-500' : 'text-red-500' }}">{{ $answer->points > 0 ? '+' : '' }}{{ $answer->points }}</span>
                                </div>
                                <p class="text-[11px] font-black text-white uppercase tracking-tight truncate">{{ $answer->text }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar Area -->
        <div class="lg:col-span-4 xl:col-span-3 space-y-8">
            <!-- Scoreboard -->
            <div class="bg-slate-900/50 rounded-[2.5rem] border border-white/5 shadow-2xl p-8 sticky top-28 backdrop-blur-xl">
                <h3 class="text-xs font-black uppercase tracking-[0.2em] text-slate-500 mb-6 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    Leaderboard
                </h3>
                
                <div class="space-y-3">
                    @foreach($activePlayers as $index => $player)
                        <div class="flex items-center justify-between group bg-slate-800/40 rounded-2xl p-4 border border-slate-700/50 hover:border-slate-600 transition shadow-inner">
                            <div class="flex items-center gap-3">
                                <div class="w-6 h-6 rounded-lg bg-slate-700/30 border border-slate-700 flex items-center justify-center text-[10px] font-black text-slate-500 shadow-inner">
                                    {{ $index + 1 }}
                                </div>
                                <div class="flex flex-col">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2.5 h-2.5 rounded-full shadow-[0_0_8px_rgba(255,255,255,0.1)]" style="background-color: {{ $player->color }}"></div>
                                        <span class="text-sm font-black text-white uppercase tracking-tight">{{ $player->name }}</span>
                                    </div>
                                    @if($player->doublesRemaining() > 0)
                                        <span class="text-[8px] font-black text-yellow-500 uppercase tracking-widest mt-0.5">{{ $player->doublesRemaining() }} DOUBLES REMAINING</span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xl font-black text-white tracking-tighter">{{ $player->total_score }}</span>
                                <button wire:click="confirmRemovePlayer('{{ $player->id }}')" 
                                        class="opacity-0 group-hover:opacity-100 text-slate-600 hover:text-red-500 transition px-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($removedPlayers->count() > 0)
                    <div class="mt-8 pt-6 border-t border-slate-800/50">
                        <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-600 mb-3">On Sideline</h4>
                        <div class="space-y-2">
                            @foreach($removedPlayers as $player)
                                <div class="flex items-center justify-between py-2 px-3 rounded-xl bg-slate-800/20 opacity-50 grayscale group hover:grayscale-0 hover:opacity-100 transition duration-300 border border-transparent hover:border-slate-700">
                                    <span class="text-xs font-black uppercase text-slate-400 line-through">{{ $player->name }}</span>
                                    <button wire:click="restorePlayer('{{ $player->id }}')" class="text-[10px] font-black uppercase text-blue-500 hover:text-blue-400">Restore</button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </main>

    <!-- Remove Player Modal -->
    @if($confirmingRemovePlayerId)
        @php $playerToRemove = $this->game->players()->find($confirmingRemovePlayerId); @endphp
        @if($playerToRemove)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-slate-900/90 backdrop-blur-xl transition-opacity" wire:click="cancelRemovePlayer"></div>
                <div class="relative bg-slate-800 rounded-[3rem] shadow-2xl w-full max-w-md border border-white/5 p-12 text-center overflow-hidden">
                    <div class="absolute -top-10 -right-10 w-40 h-40 bg-red-600/10 blur-[60px] rounded-full"></div>
                    
                    <div class="w-24 h-24 bg-red-600/10 rounded-full flex items-center justify-center mx-auto mb-8 border-2 border-red-600/20 relative z-10">
                        <svg class="w-12 h-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </div>
                    
                    <h3 class="text-3xl font-black text-white mb-4 uppercase tracking-tighter leading-none">REMOVE PLAYER?</h3>
                    <p class="text-slate-400 mb-10 text-sm font-bold uppercase tracking-widest leading-relaxed">
                        TEMPORARILY KICK <span class="text-white font-black" style="color: {{ $playerToRemove->color }}">{{ $playerToRemove->name }}</span> FROM THE GAME?
                    </p>
                    
                    <div class="flex flex-col gap-4 relative z-10">
                        <button wire:click="removePlayer" class="w-full bg-red-600 border-b-8 border-red-800 hover:bg-red-500 text-white py-5 rounded-2xl font-black uppercase tracking-widest transition-all active:translate-y-1 active:border-b-0 shadow-2xl shadow-red-600/20">Remove Now</button>
                        <button wire:click="cancelRemovePlayer" class="w-full py-4 text-slate-600 font-black uppercase tracking-widest text-[10px] hover:text-white transition">Cancel Action</button>
                    </div>
                </div>
            </div>
        @endif
    @endif

    <script data-navigate-track>
        (function() {
            const gameId = '{{ $game->id }}';
            const channel = new BroadcastChannel('friction-game-' + gameId);
            window.frictionChannel = channel;
            
            document.addEventListener('game-state-updated', function(event) {
                const state = event.detail.state || event.detail;
                channel.postMessage(state);
            });

            channel.onmessage = function(event) {
                if (event.data && event.data.type === 'request-state') {
                    const component = Livewire.all()[0];
                    if (component) component.$wire.handleBroadcastState();
                }
            };

            setTimeout(function() {
                const component = Livewire.all()[0];
                if (component) component.$wire.handleBroadcastState();
            }, 500);
        })();
    </script>
</div>
