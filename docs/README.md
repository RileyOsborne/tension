# Friction Documentation

Welcome to the Friction trivia game documentation. This guide covers game rules, architecture, and implementation details.

## Documentation Index

| Document | Description |
|----------|-------------|
| [GAME_RULES.md](./GAME_RULES.md) | Scoring, doubles, turn order, timers |
| [ARCHITECTURE.md](./ARCHITECTURE.md) | System design, two-tab pattern, real-time communication |
| [DATA_MODELS.md](./DATA_MODELS.md) | Database structure and relationships |
| [STATE_MACHINES.md](./STATE_MACHINES.md) | Game and round state transitions |
| [PLAYER_CONNECTIONS.md](./PLAYER_CONNECTIONS.md) | Multi-device support, heartbeats |
| [ANSWER_MATCHING.md](./ANSWER_MATCHING.md) | Fuzzy matching algorithm |
| [DESIGN_DECISIONS.md](./DESIGN_DECISIONS.md) | Why things work the way they do |

## Quick Start for Developers

1. **Understand the game** - Read [GAME_RULES.md](./GAME_RULES.md) first
2. **Understand the architecture** - Read [ARCHITECTURE.md](./ARCHITECTURE.md)
3. **Play a game locally** - Best way to understand the flow
4. **Reference state machines** - Check [STATE_MACHINES.md](./STATE_MACHINES.md) when modifying game flow

## Key Concepts

### Two-Tab Architecture
The Game Master (GM) runs two browser tabs on the same machine:
- **Control Tab** (`/games/{id}/control`) - Livewire component for GM actions
- **Presentation Tab** (`/games/{id}/present`) - JavaScript-driven display for players

### State Machine
All game flow is managed by `GameStateMachine` - the single source of truth for state changes.

### Multi-Device Support
Players join from their own devices via join codes. The system handles connections, disconnections, and reconnections gracefully.

## Tech Stack

- **Backend**: Laravel 12, PHP 8.2+
- **Frontend**: Livewire 3/Volt, Alpine.js, Tailwind CSS
- **Real-time**: Laravel Reverb (WebSockets), BroadcastChannel API
- **Database**: SQLite
