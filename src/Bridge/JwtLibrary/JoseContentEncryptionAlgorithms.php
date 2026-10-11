<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

use Beffroi\Core\Bridge\Algorithm\WiredAlgorithms;
use Beffroi\Core\Port\ContentEncryptionAlgorithmsInterface;

/**
 * The JWE content encryption algorithms the wiring gave the provider, read by the decrypter and by the
 * discovery document alike.
 */
final readonly class JoseContentEncryptionAlgorithms implements ContentEncryptionAlgorithmsInterface
{
    /**
     * @var WiredAlgorithms<JoseContentEncryptionAlgorithmInterface>
     */
    private WiredAlgorithms $wired;

    /**
     * @param iterable<JoseContentEncryptionAlgorithmInterface> $algorithms in the order they are wired, none of them twice
     */
    public function __construct(iterable $algorithms)
    {
        $this->wired = new WiredAlgorithms($algorithms);
    }

    /**
     * @return list<JoseContentEncryptionAlgorithmInterface>
     */
    public function all(): array
    {
        return $this->wired->algorithms;
    }

    public function named(string $name): JoseContentEncryptionAlgorithmInterface
    {
        return (
            $this->wired->named($name) ?? throw $this->wired->refusalOf(
                $name,
                'This provider does not encrypt a payload with "%s"; it encrypts with %s.',
                'This provider encrypts no payload; "%s" is not read.',
            )
        );
    }
}
