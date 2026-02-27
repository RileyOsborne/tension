<?php

namespace App\Enums;

enum GameStatus: string
{
    case Draft = 'draft';
    case Ready = 'ready';
    case Playing = 'playing';
    case Completed = 'completed';

    /**
     * Get the allowed transitions from this state.
     */
    public function allowedTransitions(): array
    {
        return match($this) {
            self::Draft => [self::Ready],
            self::Ready => [self::Draft, self::Playing],
            self::Playing => [self::Ready, self::Completed],
            self::Completed => [self::Ready],
        };
    }

    /**
     * Check if this state can transition to the target state.
     */
    public function canTransitionTo(GameStatus $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
