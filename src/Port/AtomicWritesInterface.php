<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Closure;

/**
 * Writes made inside writes are part of the outer ones.
 */
interface AtomicWritesInterface
{
    /**
     * @template T
     *
     * @param Closure(): T $writes
     *
     * @return T
     */
    public function atomically(Closure $writes): mixed;
}
