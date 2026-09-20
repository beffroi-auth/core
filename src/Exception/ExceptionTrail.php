<?php

declare(strict_types=1);

namespace Heidi\Core\Exception;

use function implode;
use function preg_replace;
use Throwable;
use function trim;

/**
 * What failed and why, one line: the message of an exception and of every cause under it, as a bridge
 * names the KMS it failed to reach or the key it does not know. Never the material a cause may wrap.
 */
final readonly class ExceptionTrail
{
    public static function of(Throwable $exception): string
    {
        $messages = [];
        for ($cause = $exception; $cause !== null; $cause = $cause->getPrevious()) {
            // a bridge may lay its message out on several lines
            $messages[] = trim((string) preg_replace('/\s+/', ' ', $cause->getMessage()));
        }

        return implode(' ', $messages);
    }
}
