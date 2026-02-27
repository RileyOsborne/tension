<?php

namespace App\Exceptions;

use Exception;
use UnitEnum;

class InvalidStateTransitionException extends Exception
{
    public function __construct(
        public readonly UnitEnum $from,
        public readonly UnitEnum $to,
        public readonly ?string $context = null
    ) {
        $message = sprintf(
            'Invalid state transition from %s to %s',
            $from->value,
            $to->value
        );

        if ($context) {
            $message .= " ({$context})";
        }

        parent::__construct($message);
    }
}
