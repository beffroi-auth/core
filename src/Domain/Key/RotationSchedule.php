<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Key;

use DateInterval;

final readonly class RotationSchedule
{
    /**
     * @param DateInterval $age how long a key works before a successor is staged, counted from its activation
     */
    public function __construct(
        public DateInterval $stagingDelay,
        public DateInterval $age,
    ) {
    }
}
