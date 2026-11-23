<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Key\TenantKey;
use Beffroi\Core\Exception\RuntimeException;

/**
 * What still needs a retiring key past its retirement window, whose retirement waits for it.
 */
interface RetirementHoldInterface
{
    /**
     * @throws RuntimeException when what holds the key cannot be read
     */
    public function holds(TenantKey $key): bool;

    /**
     * What holds the key, as an operator who asks for its retirement is told.
     */
    public function name(): string;
}
