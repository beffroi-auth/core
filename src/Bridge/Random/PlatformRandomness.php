<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\Random;

use Heidi\Core\Port\RandomnessInterface;
use function random_bytes;

/**
 * The random source of the platform, which is the one every secret of this provider is made of.
 */
final readonly class PlatformRandomness implements RandomnessInterface
{
    public function bytes(int $length): string
    {
        return random_bytes($length);
    }
}
