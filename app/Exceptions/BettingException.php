<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a betting / fight-state rule is violated. The message is safe to
 * show to the operator.
 */
class BettingException extends RuntimeException
{
}
