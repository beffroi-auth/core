<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Closure;

/**
 * What runs alone among every process of a deployment, under a name.
 */
interface ExclusiveSectionsInterface
{
    /**
     * Waits until no section of that name runs in any process of the deployment, then runs this one.
     *
     * @template T
     *
     * @param Closure(): T $section
     *
     * @param-immediately-invoked-callable $section
     *
     * @return T
     */
    public function exclusively(string $name, Closure $section): mixed;
}
