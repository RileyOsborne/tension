<x-layouts.app title="Join {{ $game->name }}">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="w-full max-w-sm">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold">{{ $game->name }}</h1>
                <p class="text-slate-400 mt-2">Enter your name to join</p>
            </div>

            <form method="POST" action="{{ route('player.register', $game->join_code) }}" class="space-y-6">
                @csrf
                <div>
                    <input type="text"
                           name="name"
                           placeholder="Your name"
                           maxlength="255"
                           autofocus
                           class="w-full text-center text-2xl
                                  bg-slate-800 border-2 border-slate-600 rounded-xl px-6 py-4
                                  focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50
                                  placeholder-slate-500">
                    @error('name')
                        <p class="text-red-400 text-center mt-2">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 py-4 rounded-xl font-bold text-xl transition">
                    Join Game
                </button>
            </form>

            <div class="text-center mt-8">
                <a href="{{ route('player.join') }}" class="text-slate-500 hover:text-slate-300 text-sm">
                    &larr; Enter a different code
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>
