<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Chain;

use DateTimeImmutable;

final readonly class VerifiedChain
{
    /**
     * @param DateTimeImmutable|null $firstMoment the moment of the first row kept, null for a chain that keeps none
     */
    public function __construct(
        public string $chain,
        public Link $start,
        public Link $end,
        public ?Checkpoint $signed,
        public ?DateTimeImmutable $firstMoment,
    ) {
    }

    public function unsigned(): int
    {
        return $this->end->position - ($this->signed->head->position ?? $this->start->position);
    }
}
