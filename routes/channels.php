<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Public game channel - anyone can listen for game state updates
Broadcast::channel('game.{gameId}', function () {
    return true;
});
