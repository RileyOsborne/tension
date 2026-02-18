<?php

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
Volt::route('/categories/{category}', 'pages.categories.edit')->name('categories.edit');

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
