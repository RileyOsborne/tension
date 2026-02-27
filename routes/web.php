<?php

use App\Http\Controllers\PlayerJoinController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// Home - redirect to games
Route::get('/', function () {
    return redirect()->route('games.index');
});

// Rules page
Volt::route('/rules', 'pages.rules')->name('rules');

// Category management
Volt::route('/categories', 'pages.categories.index')->name('categories.index');
Volt::route('/categories/create', 'pages.categories.create')->name('categories.create');
Volt::route('/categories/{category}', 'pages.categories.show')->name('categories.show');
Volt::route('/categories/{category}/edit', 'pages.categories.edit')->name('categories.edit');

// Game management
Volt::route('/games', 'pages.games.index')->name('games.index');
Volt::route('/games/create', 'pages.games.create')->name('games.create');
Volt::route('/games/{game}', 'pages.games.show')->name('games.show');
Volt::route('/games/{game}/edit', 'pages.games.edit')->name('games.edit');

// Gameplay (Two-Tab System)
Volt::route('/games/{game}/control', 'pages.games.control')->name('games.control');

// Presentation view (regular Blade, not Livewire - it's all JavaScript)
Route::get('/games/{game}/present', function (\App\Models\Game $game) {
    $game->load(['players', 'rounds.category.answers']);
    return view('games.present', compact('game'));
})->name('games.present');

// Player device routes
Route::get('/join', [PlayerJoinController::class, 'index'])->name('player.join');
Route::post('/join', [PlayerJoinController::class, 'findGame'])->name('player.join.find');

// Game-specific join routes with friendly 404 handling
Route::get('/join/{game:join_code}', [PlayerJoinController::class, 'selectPlayer'])
    ->name('player.select')
    ->missing(fn() => redirect()->route('player.join')->withErrors(['join_code' => 'Game not found. Please check the code and try again.']));
Route::post('/join/{game:join_code}/claim', [PlayerJoinController::class, 'claimPlayer'])
    ->name('player.claim')
    ->missing(fn() => redirect()->route('player.join')->withErrors(['join_code' => 'Game not found.']));
Route::post('/join/{game:join_code}/reconnect', [PlayerJoinController::class, 'reconnectPlayer'])
    ->name('player.reconnect')
    ->missing(fn() => redirect()->route('player.join')->withErrors(['join_code' => 'Game not found.']));
Route::post('/join/{game:join_code}/register', [PlayerJoinController::class, 'registerPlayer'])
    ->name('player.register')
    ->missing(fn() => redirect()->route('player.join')->withErrors(['join_code' => 'Game not found.']));

// Player game view (Livewire)
Volt::route('/play/{game}', 'pages.player.play')->name('player.play');

// Player heartbeat (called by polling to keep session alive)
Route::post('/api/player/heartbeat', [PlayerJoinController::class, 'heartbeat'])->name('player.heartbeat');

// Player disconnect (beacon API for immediate disconnect on page close)
Route::post('/api/player/disconnect', [PlayerJoinController::class, 'disconnect'])->name('player.disconnect');
