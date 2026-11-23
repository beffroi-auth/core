<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Clock;

use Beffroi\Core\Port\MonotonicClockInterface;
use function hrtime;
use function intdiv;
use function usleep;

final readonly class PlatformMonotonicClock implements MonotonicClockInterface
{
    public function now(): int
    {
        return hrtime(true);
    }

    public function waitFor(int $nanoseconds): void
    {
        usleep(intdiv($nanoseconds, 1000));
    }
}
