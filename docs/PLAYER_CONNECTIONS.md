# Player Connections

This document describes how Friction handles multi-device play, including player types, connection tracking, and reconnection logic.

## Player Types

### GM-Created Players
- Created by the Game Master in the control panel
- Have no `PlayerSession` records
- Always considered "connected" (GM controls them)
- Can be "claimed" by a real player device

### Self-Registered Players
- Joined via the `/join/{code}` page
- Have `PlayerSession` records with heartbeats
- Can disconnect and reconnect
- GM takes control if disconnected during gameplay

## Connection States

```
┌──────────────────┐
│   GM-Created     │
│  (no sessions)   │
└────────┬─────────┘
         │ player claims slot
         ▼
┌──────────────────┐    disconnect    ┌──────────────────┐
│    Connected     │◄────────────────►│   Disconnected   │
│   (heartbeat)    │    reconnect     │  (GM controls)   │
└──────────────────┘                  └──────────────────┘
         │
         │ GM removes player
         ▼
┌──────────────────┐
│     Removed      │
│  (removed_at)    │
└──────────────────┘
```

## PlayerConnectionService

**File**: `app/Services/PlayerConnectionService.php`

The service is the single source of truth for connection status.

### Constants

```php
const TIMEOUT_SECONDS = 15;  // 3x heartbeat interval
```

### Key Methods

| Method | Description |
|--------|-------------|
| `isConnected($player)` | Has recent heartbeat (or GM-created) |
| `isGmCreated($player)` | Created by GM (no sessions) |
| `isActive($player)` | Participating in game |
| `isGmControlled($player)` | GM should control this player |
| `isAvailableToClaim($player)` | Can be claimed on join page |

### isActive Logic

```php
public function isActive(Player $player): bool
{
    // Removed players are never active
    if ($player->isRemoved()) {
        return false;
    }

    // GM-created players are always active
    if ($this->isGmCreated($player)) {
        return true;
    }

    // During gameplay, all players who joined remain active
    // (GM takes over if disconnected)
    $game = $player->game;
    if ($game && in_array($game->status, ['playing', 'completed'])) {
        return true;
    }

    // In lobby, only connected players are active
    return $this->isConnected($player);
}
```

### isGmControlled Logic

```php
public function isGmControlled(Player $player): bool
{
    // GM-created = always GM controlled
    if ($this->isGmCreated($player)) {
        return true;
    }

    // Self-registered but disconnected during gameplay
    $game = $player->game;
    if ($game && in_array($game->status, ['playing', 'completed'])) {
        return !$this->isConnected($player);
    }

    return false;
}
```

## Heartbeat System

Players send heartbeats every 5 seconds:

```javascript
// Player device sends heartbeat
setInterval(() => {
    fetch('/api/heartbeat', {
        method: 'POST',
        body: JSON.stringify({ session_token: token })
    });
}, 5000);
```

The server updates `last_seen_at`:

```php
public function heartbeat(string $sessionToken): void
{
    $session = PlayerSession::where('session_token', $sessionToken)->first();
    if (!$session) return;

    $wasDisconnected = $session->last_seen_at < now()->subSeconds(self::TIMEOUT_SECONDS);
    $session->update(['last_seen_at' => now()]);

    // Broadcast reconnection event if was disconnected
    if ($wasDisconnected) {
        event(new PlayerJoinedGame($game, $player));
    }
}
```

## Connection Timeout

Players are considered disconnected if no heartbeat for 15 seconds:

```php
public function isConnected(Player $player): bool
{
    if ($this->isGmCreated($player)) {
        return true;  // GM-created are always "connected"
    }

    return $player->sessions()
        ->where('last_seen_at', '>', now()->subSeconds(self::TIMEOUT_SECONDS))
        ->exists();
}
```

## Join Flow

### New Player
1. Enter name on `/join/{code}`
2. Server creates `Player` record
3. Server creates `PlayerSession` with unique token
4. Token stored in localStorage
5. Player redirected to `/play/{game}`

### Claiming GM-Created Slot
1. Player sees available slots on join page
2. Clicks to claim a slot
3. Server creates `PlayerSession` linking to existing `Player`
4. Player redirected to `/play/{game}`

### Reconnecting
1. Player visits `/join/{code}` with existing session
2. Server finds player by session token
3. Old sessions marked stale
4. New session created
5. Player redirected back to game

## Player Removal

The GM can remove players:

```php
$player->update(['removed_at' => now()]);
```

Removed players:
- Are excluded from turn order
- Don't appear in active player lists
- Cannot reconnect
- Keep their score history

## Lobby vs Gameplay Behavior

| State | Connected | Disconnected |
|-------|-----------|--------------|
| **Lobby** | Active, can submit | Not shown, can reconnect |
| **Gameplay** | Active, can submit | Active (GM controls), can reconnect |

During gameplay, disconnected players remain active so the game can continue - the GM simply enters their answers for them.

## Broadcasting

Connection changes broadcast to all clients:

```php
event(new PlayerJoinedGame($game, $player));  // Join/reconnect
event(new PlayerLeftGame($game, $player));    // Disconnect
```

Control panel and player views update to show connection status.
