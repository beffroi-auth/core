<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Chain;

final readonly class RelinkedChain
{
    public function __construct(
        public Link $start,
        public Link $head,
    ) {
    }
}
