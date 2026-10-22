<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Closure;

/**
 * Several writes that are one fact, kept all or none (issue #781): a person an upstream provider creates is
 * the person, its source and its linked identity, and a person written without the identity that names it
 * is a person no sign-in finds whose address refuses the next creation.
 *
 * What a use case hands over is its writes and nothing else, its refusals raised before: a store that keeps
 * nothing of a failure is the promise, and what each write is stays the business of the port it goes
 * through. Writes made inside writes are part of the outer ones.
 */
interface AtomicWritesInterface
{
    /**
     * @template T
     *
     * @param Closure(): T $writes
     *
     * @return T what the writes returned, once every one of them is kept
     */
    public function atomically(Closure $writes): mixed;
}
