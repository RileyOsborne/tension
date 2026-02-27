# State Machines

Friction uses two state machines to manage game flow: one for the overall game and one for each round.

## Game Status

**File**: `app/Enums/GameStatus.php`

```
                    ┌─────────┐
                    │  Draft  │
                    └────┬────┘
                         │ categories assigned
                         ▼
        ┌───────────────────────────────┐
        │                               │
        ▼                               │
   ┌─────────┐     start game     ┌─────┴─────┐
   │  Ready  │───────────────────►│  Playing  │
   └────┬────┘                    └─────┬─────┘
        ▲                               │
        │ return to setup               │
        │◄──────────────────────────────┤
        │                               │
        │         all rounds done       │
        │                               ▼
        │                        ┌───────────┐
        │    play again          │ Completed │
        └────────────────────────┤           │
                                 └───────────┘
```

### States

| State | Description |
|-------|-------------|
| `Draft` | Game created, setting up players/categories |
| `Ready` | Setup complete, waiting to start |
| `Playing` | Game in progress |
| `Completed` | All rounds finished |

### Transitions

| From | To | Trigger |
|------|-----|---------|
| Draft | Ready | Categories assigned |
| Ready | Draft | Edit game setup |
| Ready | Playing | `startGame()` |
| Playing | Ready | `returnToSetup()` |
| Playing | Completed | `completeGame()` (all rounds done) |
| Completed | Ready | Play again |

### Code

```php
public function allowedTransitions(): array
{
    return match($this) {
        self::Draft => [self::Ready],
        self::Ready => [self::Draft, self::Playing],
        self::Playing => [self::Ready, self::Completed],
        self::Completed => [self::Ready],
    };
}
```

## Round Status

**File**: `app/Enums/RoundStatus.php`

```
┌─────────┐
│ Pending │
└────┬────┘
     │ round starts
     ▼
┌─────────┐     start         ┌────────────┐
│  Intro  │──────────────────►│ Collecting │◄─────┐
└─────────┘                   └──────┬─────┘      │
     ▲                               │            │
     │ go back                       │ all        │ go back
     └───────────────────────────────┤ answered   │
                                     ▼            │
                              ┌───────────┐       │
                         ┌───►│ Revealing │───────┘
                         │    └─────┬─────┘
                         │          │
              go back    │          │ slide > 10
                         │          ▼
                         │    ┌───────────┐
                         ├────│  Friction  │
                         │    └─────┬─────┘
                         │          │
              go back    │          │ all revealed
                         │          ▼
                         │    ┌───────────┐
                         └────│  Scoring  │
                              └─────┬─────┘
                                    │ next round
                                    ▼
                              ┌───────────┐
                              │ Complete  │
                              └───────────┘
```

### States

| State | Description |
|-------|-------------|
| `Pending` | Round not yet started |
| `Intro` | Showing category to players |
| `Collecting` | Players submitting answers (timer running) |
| `Revealing` | Showing answers #1-10 |
| `Friction` | Showing answers #11+ (friction zone) |
| `Scoring` | Displaying round scores |
| `Complete` | Round finished |

### Transitions

| From | To | Trigger |
|------|-----|---------|
| Pending | Intro | Round starts |
| Intro | Collecting | `startCollecting()` |
| Intro | Pending | Go back |
| Collecting | Revealing | `startRevealing()` (all answered) |
| Collecting | Intro | `goBackToIntro()` |
| Revealing | Friction | `revealNext()` when slide > 10 |
| Revealing | Scoring | `showScores()` |
| Revealing | Collecting | `goBackToCollecting()` |
| Friction | Scoring | `showScores()` |
| Friction | Revealing | `goBackToRevealing()` |
| Scoring | Complete | `nextRound()` |
| Scoring | Revealing/Friction | `goBackToRevealing()` |
| Complete | Pending | Next game cycle |

### Why Backward Transitions?

The GM needs flexibility to handle mistakes:
- **Collecting → Intro**: Wrong category displayed
- **Revealing → Collecting**: Need to fix a submitted answer
- **Scoring → Revealing**: Missed showing an answer

### Code

```php
public function allowedTransitions(): array
{
    return match($this) {
        self::Pending => [self::Intro],
        self::Intro => [self::Collecting, self::Pending],
        self::Collecting => [self::Revealing, self::Intro],
        self::Revealing => [self::Friction, self::Scoring, self::Collecting],
        self::Friction => [self::Scoring, self::Revealing],
        self::Scoring => [self::Complete, self::Revealing, self::Friction],
        self::Complete => [self::Pending],
    };
}
```

## GameStateMachine Methods

**File**: `app/Services/GameStateMachine.php`

| Method | From State | To State | Notes |
|--------|------------|----------|-------|
| `startGame()` | Ready | Playing | Sets round 1 to Intro |
| `completeGame()` | Playing | Completed | Marks categories as played |
| `returnToSetup()` | Playing | Ready | Resets all rounds |
| `startCollecting()` | Intro | Collecting | Starts timer |
| `startRevealing()` | Collecting | Revealing | Stops timer |
| `revealNext()` | Revealing | Revealing/Friction | Increments slide |
| `revealAll()` | Revealing | Revealing/Friction | Shows all answers |
| `showScores()` | Revealing/Friction | Scoring | Recalculates scores |
| `nextRound()` | Scoring | Complete + next Intro | Advances round |
| `goBackToIntro()` | Collecting | Intro | Stops timer |
| `goBackToCollecting()` | Revealing | Collecting | Restarts timer |
| `goBackToRevealing()` | Scoring/Friction | Revealing | - |

## State Validation

All transitions are validated:

```php
protected function transitionRound(Round $round, RoundStatus $target): void
{
    $current = RoundStatus::tryFrom($round->status);

    if ($current && !$current->canTransitionTo($target)) {
        throw new InvalidStateTransitionException($current, $target, "round");
    }

    $round->update(['status' => $target->value]);
}
```

Invalid transitions throw `InvalidStateTransitionException`.

## Timer Behavior

| Transition | Timer Action |
|------------|--------------|
| → Collecting | Start countdown (first player) |
| Collecting → Revealing | Stop timer |
| Collecting → Intro | Stop timer |
| → Collecting (go back) | Restart timer |
| Player submits | Reset timer_started_at (for countup) |
| All answered | Stop timer |
