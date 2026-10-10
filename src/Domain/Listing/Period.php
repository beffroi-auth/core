<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Listing;

use DateTimeImmutable;
use InvalidArgumentException;
use function sprintf;

/**
 * The stretch of time a table of the account is read over (architecture §5.2): from a day, to a day, or
 * either of the two alone.
 *
 * A reader looking for one afternoon in a journal of a year is reading a stretch of time and not a page
 * number, so a table that grows without end is filtered before it is paged. Both ends are open, because
 * what somebody asks of a journal is as often "since Monday" as it is "that week": a period that names
 * neither end is no period at all and is nothing rather than every row.
 *
 * The ends are days and not instants: that is what a person types and what a browser offers, and what it
 * means is the whole of both days, the first from its first second and the last to its last. A period that
 * ends before it starts is refused rather than read as empty, a table that answers nothing to a question
 * nobody can have meant being a page somebody reloads.
 */
final readonly class Period
{
    /**
     * @throws InvalidArgumentException when the period ends before it starts
     */
    private function __construct(
        public ?DateTimeImmutable $from,
        public ?DateTimeImmutable $to,
    ) {
        if ($from !== null && $to !== null && $to < $from) {
            throw new InvalidArgumentException(sprintf(
                'A period from %s to %s ends before it starts.',
                $from->format('Y-m-d H:i:s'),
                $to->format('Y-m-d H:i:s'),
            ));
        }
    }

    /**
     * The whole of both days the two ends name, the first from its first second and the last to its last;
     * null for an end nobody named, and null for a period that names neither.
     *
     * @throws InvalidArgumentException when the last day is before the first
     */
    public static function ofDays(?DateTimeImmutable $from, ?DateTimeImmutable $to): ?self
    {
        if ($from === null && $to === null) {
            return null;
        }

        return new self(
            $from?->setTime(0, 0),
            $to?->setTime(23, 59, 59, 999_999),
        );
    }
}
