<?php

declare(strict_types=1);

namespace Beffroi\Core\Exception;

use function implode;
use function preg_replace;
use Throwable;
use function trim;

/**
 * The messages of an exception and of its causes on one line, never the material a cause may wrap.
 */
final readonly class ExceptionTrail
{
    public static function of(Throwable $exception): string
    {
        $messages = [];
        for ($cause = $exception; $cause !== null; $cause = $cause->getPrevious()) {
            $messages[] = trim((string) preg_replace('/\s+/', ' ', $cause->getMessage()));
        }

        return implode(' ', $messages);
    }
}
