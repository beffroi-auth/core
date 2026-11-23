<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Journal;

final readonly class VerifiedJournal
{
    public function __construct(
        public Link $start,
        public Link $end,
        public ?Checkpoint $signed,
    ) {
    }

    public function unsigned(): int
    {
        return $this->end->position - ($this->signed?->head->position ?? $this->start->position);
    }
}
