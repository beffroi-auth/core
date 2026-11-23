<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Chain;

use DateTimeImmutable;

/**
 * A row of a chained record as the database holds it, its sealed columns as the bytes of their seals: what a link of
 * the chain is computed from.
 */
final readonly class WrittenRow
{
    /**
     * @param list<string|null> $columns the columns the record chains, in the order it names them
     */
    public function __construct(
        public string $id,
        public string $tenant,
        public DateTimeImmutable $moment,
        public array $columns,
    ) {
    }
}
