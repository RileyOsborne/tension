# Architecture

This document describes the system architecture of Friction, including the two-tab pattern, real-time communication, and key infrastructure decisions.

## Overview

Friction is a Laravel-based multiplayer trivia game designed for in-person play. The Game Master (GM) controls the game from a laptop while players participate from their phones.

```
┌─────────────────────────────────────────────────────────────┐
│                     GM's Machine                            │
│  ┌─────────────────┐         ┌─────────────────────────┐   │
│  │  Control Tab    │◄───────►│  Presentation Tab       │   │
│  │  (Livewire)     │ Browser │  (JavaScript/Alpine)    │   │
│  │  /games/{id}/   │ Channel │  /games/{id}/present    │   │
│  │  control        │         │                         │   │
│  └────────┬────────┘         └────────────────────────-┘   │
└───────────┼─────────────────────────────────────────────────┘
            │ HTTP/WebSocket
            ▼
┌───────────────────────┐      ┌───────────────────────┐
│    Laravel Server     │      │    Player Devices     │
│  ┌─────────────────┐  │      │  ┌─────────────────┐  │
│  │ GameStateMachine│  │◄────►│  │  /play/{game}   │  │
│  │ (Single Source  │  │Reverb│  │  (Livewire)     │  │
│  │  of Truth)      │  │      │  └─────────────────┘  │
│  └─────────────────┘  │      └───────────────────────┘
└───────────────────────┘
```

## Two-Tab Architecture

### Why Two Tabs?

The GM needs to see different information than what's displayed to players:
- **Control Tab**: Shows all answers, player submissions, admin controls
- **Presentation Tab**: Shows only revealed answers, dramatic reveals, scores

Separating these views allows the GM to:
1. See the full answer list without spoiling it for players
2. Enter player answers without showing the keyboard/input
3. Control timing of reveals for dramatic effect

### Communication Between Tabs

Tabs on the same machine communicate via the **BroadcastChannel API**:

```javascript
// Control tab sends state updates
const channel = new BroadcastChannel('friction-game-' + gameId);
channel.postMessage({ type: 'stateUpdate', state: newState });

// Presentation tab receives updates instantly
channel.onmessage = (event) => {
    if (event.data.type === 'stateUpdate') {
        updateDisplay(event.data.state);
    }
};
```

**Advantages**:
- Zero network latency
- Works offline
- No server round-trip needed for tab sync

## Real-Time Communication

### Laravel Reverb (WebSockets)

Remote player devices connect via Laravel Reverb on the `game.{id}` channel:

```php
// Broadcasting game state
event(new GameStateUpdated($game, $state));

// Event broadcasts to channel
public function broadcastOn(): array
{
    return [new Channel('game.' . $this->game->id)];
}
```

### Event Flow

1. **GM Action**: GM clicks button in Control tab
2. **Livewire Method**: Triggers PHP method
3. **GameStateMachine**: Updates database state
4. **Broadcast**: `GameStateUpdated` event sent via Reverb
5. **BroadcastChannel**: Control tab also posts to local channel
6. **UI Updates**: All views (Presentation, Player devices) update

## GameStateMachine

The `GameStateMachine` service is the **single source of truth** for all game state changes:

```php
// All state changes go through the state machine
$machine = new GameStateMachine($game);
$machine->startGame();
$machine->startCollecting();
$machine->revealNext();
$machine->showScores();
$machine->nextRound();
```

**Responsibilities**:
- Validate state transitions
- Update database
- Calculate scores
- Broadcast state to all clients

**Why centralized?**
- Ensures consistent state across all views
- Single place to add validation or logging
- Prevents race conditions

## Key Files

| File | Purpose |
|------|---------|
| `app/Services/GameStateMachine.php` | Central game flow orchestrator |
| `app/Services/PlayerConnectionService.php` | Connection tracking and heartbeats |
| `app/Events/GameStateUpdated.php` | WebSocket broadcast event |
| `resources/views/livewire/pages/games/control.blade.php` | GM control panel |
| `resources/views/games/present.blade.php` | Presentation display |
| `resources/views/livewire/pages/player/play.blade.php` | Player device view |

## Routes

| Route | View | Purpose |
|-------|------|---------|
| `/games` | Game list | Manage games |
| `/games/{game}/control` | Control tab | GM interface |
| `/games/{game}/present` | Presentation tab | Player-facing display |
| `/join/{code}` | Join page | Players enter join code |
| `/play/{game}` | Player view | Player device during game |

## Current Limitations

### GM Must Be Local
The two-tab architecture assumes the GM is physically present:
- Control and Presentation tabs must be on same machine for BroadcastChannel
- No remote GM support currently

### Future Considerations

**Remote GM Play**: Would require:
- Moving Presentation tab state sync to WebSockets
- Adding GM authentication
- Handling network latency for reveals

**Spectator Mode**: Would need:
- Read-only view type
- Different state filtering (hide unrevealed answers)

**Team Play**: Would require:
- Team model and relationships
- Modified turn order logic
- Team-based scoring

## System Constants

| Constant | Value | Location | Description |
|----------|-------|----------|-------------|
| `TIMEOUT_SECONDS` | 15 | PlayerConnectionService.php | Player disconnect threshold |
| `JOIN_CODE_LENGTH` | 6 | Game.php | Characters in join code |
| `POLL_INTERVAL` | 2000ms | present.blade.php | State polling frequency |
