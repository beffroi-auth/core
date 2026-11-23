<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Journal;

use DateTimeImmutable;

/**
 * An act as the journal holds it, its provenance as the bytes of its seal: what a link of the chain is computed from.
 */
final readonly class WrittenAct
{
    public function __construct(
        public string $id,
        public string $tenant,
        public string $act,
        public DateTimeImmutable $occurredAt,
        public ?string $subject,
        public ?string $client,
        public ?string $author,
        public string $sealedContext,
        public ?string $reason = null,
        public ?string $method = null,
    ) {
    }
}
