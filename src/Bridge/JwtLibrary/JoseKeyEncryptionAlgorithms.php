<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

use Beffroi\Core\Bridge\Algorithm\WiredAlgorithms;
use Beffroi\Core\Exception\UnsupportedAlgorithmException;
use Beffroi\Core\Port\KeyEncryptionAlgorithmsInterface;
use function implode;
use function sprintf;

/**
 * The JWE key management algorithms the wiring gave the provider: the encryption keys it generates, the
 * JWE it opens and what the discovery document announces all read this one, so the document cannot
 * announce an algorithm the decrypter refuses. A deployment nobody encrypts to wires none, and this list
 * is empty.
 */
final readonly class JoseKeyEncryptionAlgorithms implements KeyEncryptionAlgorithmsInterface
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

    public function named(string $name): JoseKeyEncryptionAlgorithmInterface
    {
        $algorithm = $this->wired->named($name);
        if ($algorithm !== null) {
            return $algorithm;
        }
        if ($this->wired->algorithms === []) {
            throw new UnsupportedAlgorithmException(sprintf('This provider is encrypted to with nothing; "%s" is not read.', $name));
        }

        throw new UnsupportedAlgorithmException(sprintf(
            'This provider is not encrypted to with "%s"; it is encrypted to with %s.',
            $name,
            implode(', ', $this->wired->names()),
        ));
    }
}
