# Game Rules

Friction is a multiplayer trivia game based on the British TV show format. Players try to name items from ranked "Top 10" lists, earning more points for answers closer to #10.

## Scoring System

### Top 10 Answers (Positions 1-10)
Points equal the position number:
- Position #1 = 1 point
- Position #2 = 2 points
- ...
- Position #10 = 10 points

### Friction Zone (Positions 11+)
Answers outside the top 10 are in the "friction zone":
- Positions #11-15 = **-5 points each**

### Not on List
If a player's guess doesn't match any answer:
- **-3 points**

## The Double

Each player can use their "double" **once per game**:
- Multiplies points by 2
- **Warning**: Also doubles negative points!

Examples:
- Position #5 + double = +10 points
- Position #12 (friction) + double = -10 points
- Not on list + double = -6 points

## Game Structure

### Rounds
- Total rounds = **2 × player_count**
- Example: 3 players = 6 rounds

Each player gets to answer first exactly twice over the course of a game.

### Turn Order
Turn order rotates each round:
- Round 1: Player A, B, C
- Round 2: Player B, C, A
- Round 3: Player C, A, B
- Round 4: Player A, B, C (repeats)

Formula: `offset = (roundNumber - 1) % playerCount`

### Timer
- **First player**: 30-second countdown (configurable via `thinking_time`)
- **Other players**: Count-up from 0 (no time limit, social pressure only)

The timer resets when each player submits their answer.

## Round Flow

1. **Intro**: Category is displayed to players
2. **Collecting**: Players submit their answers one at a time
3. **Revealing**: Answers are revealed from #1 to #10 (or higher)
4. **Friction**: If answers go beyond #10, friction zone activates
5. **Scoring**: Points are calculated and displayed
6. **Next Round**: Move to next category

## Configurable Parameters

These values are stored per-game in the database:

| Parameter | Default | Description |
|-----------|---------|-------------|
| `thinking_time` | 30 | Seconds for first player's countdown |
| `top_answers_count` | 10 | Number of answers in safe zone |
| `friction_penalty` | -5 | Points for friction zone answers |
| `not_on_list_penalty` | -3 | Points when answer not found |
| `rounds_per_player` | 2 | Rounds each player plays |
| `double_multiplier` | 2 | Multiplier when double is used |
| `doubles_per_player` | 1 | Number of doubles each player gets |
| `max_answers_per_category` | 15 | Maximum answers a category can have |

## Code References

- Game config defaults: `app/Models/Game.php` (booted method and helper methods)
- Scoring calculation: `app/Models/Answer.php:52-64`
- Turn order: `app/Models/Game.php:81-95`
- Timer modes: `app/Services/GameStateMachine.php:282-344`
- Double logic: `app/Models/Player.php:47-72` (canUseDouble and doublesRemaining)
