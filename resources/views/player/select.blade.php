<x-layouts.app title="Select Player - {{ $game->name }}">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="w-full max-w-sm">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold">{{ $game->name }}</h1>
                <p class="text-slate-400 mt-2">Join the game</p>
            </div>

            {{-- Disconnected players who can reconnect --}}
            @if($disconnectedPlayers->isNotEmpty())
                <div class="mb-6">
                    <p class="text-slate-400 text-sm mb-3">Reconnect as:</p>
                    <div class="space-y-3">
                        @foreach($disconnectedPlayers as $player)
                            <form method="POST" action="{{ route('player.reconnect', $game->join_code) }}">
                                @csrf
                                <input type="hidden" name="player_id" value="{{ $player->id }}">
                                <button type="submit"
                                        class="w-full p-4 rounded-xl border-2 transition text-left
                                               bg-yellow-900/20 border-yellow-600 hover:border-yellow-500 hover:bg-yellow-900/30
                                               flex items-center justify-between">
                                    <div class="flex items-center gap-4">
                                        <div class="w-4 h-4 rounded-full flex-shrink-0" style="background-color: {{ $player->color }}"></div>
                                        <span class="text-xl font-bold">{{ $player->name }}</span>
                                    </div>
                                    <span class="text-yellow-400 text-sm">Reconnect</span>
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- GM-created players available to claim --}}
            @if($availablePlayers->isNotEmpty())
                <div class="mb-6">
                    <p class="text-slate-400 text-sm mb-3">Select your player:</p>
                    <div class="space-y-3">
                        @foreach($availablePlayers as $player)
                            <form method="POST" action="{{ route('player.claim', $game->join_code) }}">
                                @csrf
                                <input type="hidden" name="player_id" value="{{ $player->id }}">
                                <button type="submit"
                                        class="w-full p-4 rounded-xl border-2 transition text-left
                                               bg-slate-800 border-slate-600 hover:border-blue-500 hover:bg-slate-700
                                               flex items-center gap-4">
                                    <div class="w-4 h-4 rounded-full flex-shrink-0" style="background-color: {{ $player->color }}"></div>
                                    <span class="text-xl font-bold">{{ $player->name }}</span>
                                </button>
                            </form>
                        @endforeach
                    </div>

                    @error('player_id')
                        <p class="text-red-400 text-center mt-4">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            @if($canRegister)
                @if($availablePlayers->isNotEmpty())
                    <div class="relative my-6">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-slate-700"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-slate-900 text-slate-500">or register as new player</span>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('player.register', $game->join_code) }}" class="space-y-4"
                      x-data="{ selectedColor: '#3B82F6' }">
                    @csrf
                    <div>
                        <input type="text"
                               name="name"
                               placeholder="Enter your name"
                               maxlength="255"
                               autofocus
                               class="w-full text-center text-xl
                                      bg-slate-800 border-2 border-slate-600 rounded-xl px-6 py-4
                                      focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50
                                      placeholder-slate-500">
                        @error('name')
                            <p class="text-red-400 text-center mt-2">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-center gap-3">
                        <span class="text-slate-400 text-sm">Your color</span>
                        <input type="color"
                               name="color"
                               x-model="selectedColor"
                               class="w-12 h-12 cursor-pointer rounded-full border-2 border-slate-600 bg-transparent p-0 [&::-webkit-color-swatch-wrapper]:p-0 [&::-webkit-color-swatch]:rounded-full [&::-webkit-color-swatch]:border-0 [&::-moz-color-swatch]:rounded-full [&::-moz-color-swatch]:border-0">
                    </div>

                    <button type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 py-4 rounded-xl font-bold text-xl transition">
                        Join Game
                    </button>
                </form>
            @elseif($availablePlayers->isEmpty())
                <div class="bg-slate-800 rounded-xl p-6 text-center">
                    <p class="text-slate-400 mb-4">All players are already connected!</p>
                    <a href="{{ route('player.join') }}" class="text-blue-400 hover:text-blue-300">
                        &larr; Try a different game
                    </a>
                </div>
            @endif

            <div class="text-center mt-8">
                <a href="{{ route('player.join') }}" class="text-slate-500 hover:text-slate-300 text-sm">
                    &larr; Enter a different code
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>
