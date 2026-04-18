<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Friction' }} - Friction</title>

    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet" />
    <link href="https://fonts.bunny.net/css?family=saira-stencil-one:400&display=swap" rel="stylesheet" />

    <style>
        .font-title { font-family: 'Saira Stencil One', cursive; }
    </style>

    <!-- Styles & Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- SortableJS for drag-and-drop -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
</head>
<body class="min-h-screen bg-slate-900 text-white antialiased">
    <nav class="bg-slate-800/50 backdrop-blur-md border-b border-white/5 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-24">
                <div class="flex items-center space-x-16">
                    <a href="/" class="text-4xl font-title"><span class="inline-flex items-baseline"><span class="text-white">FRIC</span><span class="text-red-500 ml-[0.04em]">TION</span></span></a>
                    <div class="flex items-center space-x-10 text-base font-black uppercase tracking-widest">
                        <a href="{{ route('games.index') }}" class="{{ request()->routeIs('games.*') ? 'text-white' : 'text-slate-400' }} hover:text-white transition">Games</a>
                        <a href="{{ route('categories.index') }}" class="{{ request()->routeIs('categories.*') ? 'text-white' : 'text-slate-400' }} hover:text-white transition">Categories</a>
                        <a href="{{ route('rules') }}" class="{{ request()->routeIs('rules') ? 'text-white' : 'text-slate-400' }} hover:text-white transition">Rules</a>
                    </div>
                </div>

                <div class="flex items-center space-x-8">
                    @auth
                        <div class="flex items-center space-x-6">
                            <span class="text-slate-400 text-sm font-bold uppercase tracking-widest">{{ auth()->user()->name }}</span>
                            <form method="POST" action="{{ route('logout') }}" id="logout-form">
                                @csrf
                                <button type="submit" class="text-slate-400 hover:text-red-400 text-sm font-bold uppercase tracking-widest transition">
                                    Logout
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="flex items-center space-x-8 text-sm font-bold uppercase tracking-widest">
                            <a href="{{ route('login') }}" class="{{ request()->routeIs('login') ? 'text-white' : 'text-slate-400' }} hover:text-white transition">Login</a>
                            <a href="{{ route('register') }}" class="{{ request()->routeIs('register') ? 'text-white' : 'text-slate-400' }} hover:text-white transition border-2 border-blue-600 px-6 py-2 rounded-xl hover:bg-blue-600 hover:text-white">Register</a>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main>
        {{ $slot }}
    </main>
</body>
</html>
