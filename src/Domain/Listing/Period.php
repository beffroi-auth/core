<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Listing;

use DateTimeImmutable;
use InvalidArgumentException;
use function sprintf;

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
     * From the first second of the first day to the last second of the last.
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

    public function holds(DateTimeImmutable $moment): bool
    {
        return ($this->from === null || $moment >= $this->from) && ($this->to === null || $moment <= $this->to);
    }
}
