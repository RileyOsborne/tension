# Data Models

This document describes the database structure and relationships in Friction.

## Entity Relationship Diagram

```
┌─────────────┐       ┌─────────────┐       ┌─────────────┐
│    Game     │───────│   Round     │───────│  Category   │
│             │ 1:N   │             │ N:1   │             │
└──────┬──────┘       └──────┬──────┘       └──────┬──────┘
       │                     │                     │
       │ 1:N                 │ 1:N                 │ 1:N
       ▼                     ▼                     ▼
┌─────────────┐       ┌─────────────┐       ┌─────────────┐
│   Player    │───────│PlayerAnswer │───────│   Answer    │
│             │ 1:N   │             │ N:1   │             │
└──────┬──────┘       └─────────────┘       └─────────────┘
       │
       │ 1:N
       ▼
┌─────────────┐
│PlayerSession│
└─────────────┘
```

## Models

### Game

The top-level container for a game session.

**File**: `app/Models/Game.php`

| Column | Type | Description |
|--------|------|-------------|
| `id` | ULID | Primary key |
| `name` | string | Game display name |
| `player_count` | integer | Number of players |
| `total_rounds` | integer | Calculated: player_count × 2 |
| `current_round` | integer | Current round number (1-based) |
| `status` | enum | draft, ready, playing, completed |
| `join_code` | string(6) | Unique code for players to join |
| `thinking_time` | integer | Seconds for first player (default: 30) |
| `join_mode` | string | How players can join |
| `timer_running` | boolean | Is timer active? |
| `timer_started_at` | timestamp | When timer started |
| `show_rules` | boolean | Display rules on presentation? |

**Key Methods**:
- `getTurnOrderForRound($roundNumber)`: Returns players in order for a round
- `generateJoinCode()`: Creates unique 6-character join code

### Round

Represents one round of play within a game.

**File**: `app/Models/Round.php`

| Column | Type | Description |
|--------|------|-------------|
| `id` | ULID | Primary key |
| `game_id` | ULID | Foreign key to Game |
| `category_id` | ULID | Foreign key to Category |
| `round_number` | integer | Round number within game |
| `status` | enum | pending, intro, collecting, revealing, friction, scoring, complete |
| `current_slide` | integer | Current reveal position (0 = intro) |

**Key Methods**:
- `getCurrentAnswer()`: Returns the Answer at current_slide position
- `getMaxSlide()`: Returns highest position in category
- `isOnFriction()`: Returns true if current_slide > 10

### Player

Represents a participant in a game.

**File**: `app/Models/Player.php`

| Column | Type | Description |
|--------|------|-------------|
| `id` | ULID | Primary key |
| `game_id` | ULID | Foreign key to Game |
| `name` | string | Player display name |
| `color` | string | Hex color for UI |
| `position` | integer | Order in player list |
| `total_score` | integer | Cumulative score |
| `double_used` | boolean | Has player used their double? |
| `removed_at` | timestamp | When player was removed (null if active) |

**Key Methods**:
- `canUseDouble()`: Returns true if double not yet used
- `isGmCreated()`: True if created by GM (no sessions)
- `isConnected()`: True if has recent heartbeat
- `isActive()`: True if participating in game
- `isGmControlled()`: True if GM is controlling this player

### Category

A question category with ranked answers.

**File**: `app/Models/Category.php`

| Column | Type | Description |
|--------|------|-------------|
| `id` | ULID | Primary key |
| `title` | string | Category question/title |
| `description` | string | Additional context |
| `source` | string | Where data came from |
| `played_at` | timestamp | When category was last used |

**Key Methods**:
- `isComplete()`: True if has at least 10 answers

### Answer

A single answer within a category.

**File**: `app/Models/Answer.php`

| Column | Type | Description |
|--------|------|-------------|
| `id` | ULID | Primary key |
| `category_id` | ULID | Foreign key to Category |
| `text` | string | Full answer text (may include location) |
| `stat` | string | Supporting statistic |
| `position` | integer | Rank (1-15 typically) |
| `is_friction` | boolean | Auto-calculated: position > 10 |
| `points` | integer | Auto-calculated: position or -5 |

**Key Methods**:
- `getDisplayTextAttribute()`: Returns text without location suffix

**Auto-Calculation**:
```php
// On create/update
$answer->is_friction = $answer->position > 10;
$answer->points = $answer->is_friction ? -5 : $answer->position;
```

### PlayerAnswer

Junction table tracking what each player answered in each round.

**File**: `app/Models/PlayerAnswer.php`

| Column | Type | Description |
|--------|------|-------------|
| `id` | ULID | Primary key |
| `round_id` | ULID | Foreign key to Round |
| `player_id` | ULID | Foreign key to Player |
| `answer_id` | ULID (nullable) | Foreign key to Answer (null = not on list) |
| `points_awarded` | integer | Final points including double |
| `was_doubled` | boolean | Did player use double? |
| `submission_source` | string | How answer was submitted |
| `answer_order` | integer | Order submitted in round |

### PlayerSession

Tracks player device connections.

**File**: `app/Models/PlayerSession.php`

| Column | Type | Description |
|--------|------|-------------|
| `id` | ULID | Primary key |
| `player_id` | ULID | Foreign key to Player |
| `game_id` | ULID | Foreign key to Game |
| `session_token` | string(64) | Unique session identifier |
| `device_name` | string | Device identifier |
| `is_connected` | boolean | Connection status |
| `last_seen_at` | timestamp | Last heartbeat time |

## Model Responsibilities

### What Models Handle
- Data access and relationships
- Attribute casting
- Simple computed properties
- Delegating to services for complex logic

### What Services Handle
- `GameStateMachine`: All game flow and state transitions
- `PlayerConnectionService`: Connection status, heartbeats

Example of delegation:
```php
// Player model delegates connection logic to service
public function isConnected(): bool
{
    return app(PlayerConnectionService::class)->isConnected($this);
}
```

## ULIDs

All models use ULIDs (Universally Unique Lexicographically Sortable Identifiers) instead of auto-incrementing integers:

```php
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Game extends Model
{
    use HasUlids;
}
```

**Advantages**:
- Sortable by creation time
- No sequential ID exposure
- Safe for distributed systems
