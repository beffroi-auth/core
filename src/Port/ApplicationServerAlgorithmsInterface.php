<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Key\ApplicationServerAlgorithmInterface;
use Beffroi\Core\Exception\UnsupportedAlgorithmException;

/**
 * The algorithms of the keys the tenant hands out itself, the one source of truth over them: what the key
 * commands take on their "--algorithm" option for one of them, what draws such a pair and what reads a
 * stored one back all read this one (issue #98). A deployment that wires none has none, and the pages
 * that would hand one out are not served.
 */
interface ApplicationServerAlgorithmsInterface extends KeyAlgorithmsInterface
{
    /**
     * @return list<ApplicationServerAlgorithmInterface> in the order they are wired
     */
    public function all(): array;

    /**
     * @throws UnsupportedAlgorithmException when nothing wired carries that name
     */
    public function named(string $name): ApplicationServerAlgorithmInterface;
}
