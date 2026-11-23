<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity;

use Beffroi\Core\Domain\Chain\ChainInterface;

final readonly class ClaimHistoryChain implements ChainInterface
{
    public const string NAME = 'claim_history';

    public function name(): string
    {
        return self::NAME;
    }
}
