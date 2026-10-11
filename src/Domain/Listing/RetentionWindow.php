<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Listing;

use DateTimeImmutable;
use InvalidArgumentException;
use function sprintf;

/**
 * How long a store of this provider keeps what it holds, and the moment before which it keeps nothing: the
 * number of days a deployment named, and the instant that number reaches back to.
 *
 * It is one object because the two are one answer. A step forgets what is before the moment, and a reading
 * that reaches past it says how many days this deployment keeps and the day that reaches (`JournalRetention`,
 * `ClaimHistoryRetention`, the register of the module): a number said in one place and a moment computed in
 * another is a register that says it holds six months and answers four.
 *
 * A window that keeps nothing is refused rather than read as a store that forgets everything it is handed:
 * nought days is a value somebody typed, never a duration a deployment means.
 */
final readonly class RetentionWindow
{
    /**
     * @throws InvalidArgumentException when it keeps less than a day
     */
    public function __construct(
        public int $days,
    ) {
        if ($days < 1) {
            throw new InvalidArgumentException(sprintf('A retention window keeps a day or more, "%d" given.', $days));
        }
    }

    /**
     * The oldest moment a store under this window still holds a row of. What is before it was forgotten, or
     * is about to be by the next tick of the step that forgets.
     *
     * The instant is given and not read from a clock: a window is a value object, and what reads the clock
     * is the use case that holds one.
     */
    public function keptSince(DateTimeImmutable $now): DateTimeImmutable
    {
        return $now->modify(sprintf('-%d days', $this->days));
    }
}
