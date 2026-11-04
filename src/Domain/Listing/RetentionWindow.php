<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Listing;

use DateTimeImmutable;
use InvalidArgumentException;
use function sprintf;

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

    public function keptSince(DateTimeImmutable $now): DateTimeImmutable
    {
        return $now->modify(sprintf('-%d days', $this->days));
    }
}
