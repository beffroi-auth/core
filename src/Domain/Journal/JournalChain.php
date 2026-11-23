<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Journal;

use Beffroi\Core\Domain\Chain\ChainInterface;

final readonly class JournalChain implements ChainInterface
{
    public const string NAME = 'journal';

    public function name(): string
    {
        return self::NAME;
    }
}
