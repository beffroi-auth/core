<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Chain;

use Beffroi\Core\Exception\InvalidArgumentException;

/**
 * A row and the link stored beside it, as they are read: neither is trusted before the chain is followed.
 */
final readonly class LinkedRow
{
    public function __construct(
        public WrittenRow $row,
        public int $position,
        public string $previous,
        public string $hash,
    ) {
    }

    /**
     * @throws InvalidArgumentException when what is stored is no link
     */
    public function before(): Link
    {
        return new Link($this->position - 1, $this->previous);
    }

    public function follows(Link $before, Link $computed): bool
    {
        return $this->previous === $before->hash && $this->position === $computed->position && $this->hash === $computed->hash;
    }
}
