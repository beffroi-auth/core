<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

use Beffroi\Core\Bridge\Algorithm\WiredAlgorithms;
use Beffroi\Core\Exception\UnsupportedAlgorithmException;
use Beffroi\Core\Port\MessageSealingAlgorithmsInterface;
use Jose\Component\Core\AlgorithmManager;

/**
 * The algorithms of the keys the tenant seals what leaves the process to, as the wiring gave them. The
 * first is the one a body is sealed to the key of.
 *
 * An empty one is a deployment that seals nothing, and a deployment that seals nothing writes no message:
 * the refusal says so, rather than a body going out in the clear (decision 0020).
 */
final readonly class JoseMessageSealingAlgorithms implements JoseAlgorithmRegistryInterface, MessageSealingAlgorithmsInterface
{
    /**
     * @var WiredAlgorithms<JoseMessageSealingAlgorithmInterface>
     */
    private WiredAlgorithms $wired;

    /**
     * @param iterable<JoseMessageSealingAlgorithmInterface> $algorithms in the order they are wired, the
     *                                                                   one a body is sealed under first
     */
    public function __construct(iterable $algorithms)
    {
        $this->wired = new WiredAlgorithms($algorithms);
    }

    /**
     * @return list<JoseMessageSealingAlgorithmInterface>
     */
    public function all(): array
    {
        return $this->wired->algorithms;
    }

    public function find(string $name): ?JoseMessageSealingAlgorithmInterface
    {
        return $this->wired->named($name);
    }

    public function named(string $name): JoseMessageSealingAlgorithmInterface
    {
        return $this->find($name) ?? throw $this->refusalOf($name);
    }

    public function refusalOf(string $name): UnsupportedAlgorithmException
    {
        return $this->wired->refusalOf(
            $name,
            'This provider seals no message body to a key named "%s"; it seals to %s.',
            'This provider seals no message body to a key named "%s"; it seals to none.',
        );
    }

    public function defaultAlgorithm(): JoseMessageSealingAlgorithmInterface
    {
        return (
            $this->wired->algorithms[0] ?? throw new UnsupportedAlgorithmException('This provider is wired with no algorithm to seal a message body to: nothing it writes to a transport can leave the process.')
        );
    }

    /**
     * What seals a body and what opens one are given the same manager, built from this one list: the
     * provider cannot seal under something it would refuse to open.
     */
    public function manager(): AlgorithmManager
    {
        $jose = [];
        foreach ($this->wired->algorithms as $algorithm) {
            $jose[] = $algorithm->keyEncryption()->joseAlgorithm();
            $jose[] = $algorithm->contentEncryption()->joseAlgorithm();
        }

        return new AlgorithmManager($jose);
    }
}
