<?php

namespace App\Enums;

enum RoundStatus: string
{
    case Pending = 'pending';
    case Intro = 'intro';
    case Collecting = 'collecting';
    case Revealing = 'revealing';
    case Friction = 'friction';
    case Scoring = 'scoring';
    case Complete = 'complete';

    /**
     * Get the allowed transitions from this state.
     * Includes backward transitions for GM navigation.
     */
    public function allowedTransitions(): array
    {
        return match($this) {
            self::Pending => [self::Intro],
            self::Intro => [self::Collecting, self::Pending],
            self::Collecting => [self::Revealing, self::Intro], // Can go back to intro
            self::Revealing => [self::Friction, self::Scoring, self::Collecting], // Can go back to collecting
            self::Friction => [self::Scoring, self::Revealing], // Can go back to revealing
            self::Scoring => [self::Complete, self::Revealing, self::Friction], // Can go back to revealing/friction
            self::Complete => [self::Pending],
        };
    }

    /**
     * Check if this state can transition to the target state.
     */
    public function canTransitionTo(RoundStatus $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
