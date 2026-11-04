<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Random;

use Beffroi\Core\Port\RandomnessInterface;
use function random_bytes;

final readonly class PlatformRandomness implements RandomnessInterface
{
    public function bytes(int $length): string
    {
        return random_bytes($length);
    }
}
