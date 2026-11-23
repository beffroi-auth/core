<?php

declare(strict_types=1);

namespace Beffroi\Core\Exception;

use RuntimeException;
use Throwable;

/**
 * The message names a position and never what the act there holds.
 */
final class BrokenJournalException extends RuntimeException implements ExceptionInterface
{
    /**
     * @param int|null $position the first position that does not follow, null when the signature itself is at fault
     */
    public function __construct(
        public readonly ?int $position,
        string $message,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
