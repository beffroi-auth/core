<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\Claim\ClaimInterface;

interface ClaimsInterface
{
    public function named(string $name): ?ClaimInterface;
}
