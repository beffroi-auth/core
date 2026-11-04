<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

/**
 * An implementation is a cryptographically secure source, or a test double.
 */
interface RandomnessInterface
{
    /**
     * @param positive-int $length
     *
     * @return non-empty-string exactly $length bytes
     */
    public function bytes(int $length): string;
}
