<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

use Beffroi\Core\Bridge\Algorithm\WiredAlgorithms;
use Beffroi\Core\Exception\UnsupportedAlgorithmException;
use Beffroi\Core\Port\KeyEncryptionAlgorithmsInterface;

/**
 * The JWE key management algorithms the wiring gave the provider: the encryption keys it generates, the
 * JWE it opens and what the discovery document announces all read this one, so the document cannot
 * announce an algorithm the decrypter refuses. A deployment nobody encrypts to wires none, and this list
 * is empty.
 */
final readonly class JoseKeyEncryptionAlgorithms implements JoseAlgorithmRegistryInterface, KeyEncryptionAlgorithmsInterface
{
    /**
     * @var WiredAlgorithms<JoseKeyEncryptionAlgorithmInterface>
     */
    private WiredAlgorithms $wired;

    /**
     * @param iterable<JoseKeyEncryptionAlgorithmInterface> $algorithms in the order they are wired, none of them twice
     */
    public function __construct(iterable $algorithms)
    {
        $this->wired = new WiredAlgorithms($algorithms);
    }

    /**
     * @return list<JoseKeyEncryptionAlgorithmInterface>
     */
    public function all(): array
    {
        return $this->wired->algorithms;
    }

    public function find(string $name): ?JoseKeyEncryptionAlgorithmInterface
    {
        return $this->wired->named($name);
    }

    public function named(string $name): JoseKeyEncryptionAlgorithmInterface
    {
        return $this->find($name) ?? throw $this->refusalOf($name);
    }

    public function refusalOf(string $name): UnsupportedAlgorithmException
    {
        return $this->wired->refusalOf(
            $name,
            'This provider is not encrypted to with "%s"; it is encrypted to with %s.',
            'This provider is encrypted to with nothing; "%s" is not read.',
        );
    }
}
