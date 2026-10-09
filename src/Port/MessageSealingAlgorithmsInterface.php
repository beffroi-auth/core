<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Key\MessageSealingAlgorithmInterface;
use Beffroi\Core\Exception\UnsupportedAlgorithmException;

/**
 * The algorithms of the keys the tenant seals what leaves the process to, the one source of truth over
 * them: what the key commands take on their "--algorithm" option for one of them, what draws such a pair
 * and what reads a stored one back all read this one (issue #98).
 *
 * One of them is wired, the body of a message being sealed to the key of the one that seals; a registry
 * all the same, because the family is open by addition like every other (decision 0001) and because what
 * a command offers is derived from what is wired and never enumerated beside it.
 */
interface MessageSealingAlgorithmsInterface extends KeyAlgorithmsInterface
{
    /**
     * @return list<MessageSealingAlgorithmInterface> in the order they are wired
     */
    public function all(): array;

    /**
     * The one a body is sealed to the key of: the first wired.
     *
     * @throws UnsupportedAlgorithmException when nothing is wired, which is a deployment that seals
     *                                       nothing and may write no message
     */
    public function defaultAlgorithm(): MessageSealingAlgorithmInterface;

    /**
     * @throws UnsupportedAlgorithmException when nothing wired carries that name
     */
    public function named(string $name): MessageSealingAlgorithmInterface;
}
