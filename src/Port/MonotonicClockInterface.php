<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

/**
 * Measures a duration and waits one out; the time of day is ClockInterface's.
 */
interface MonotonicClockInterface
{
    /**
     * @return int nanoseconds from an origin of the process, never decreasing
     */
    public function now(): int;

    /**
     * @param positive-int $nanoseconds
     */
    public function waitFor(int $nanoseconds): void;
}
