<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\JwtLibrary;

use Heidi\Core\Bridge\Algorithm\WiredAlgorithms;
use Heidi\Core\Exception\UnsupportedAlgorithmException;
use Heidi\Core\Port\ContentEncryptionAlgorithmsInterface;
use function implode;
use function sprintf;

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
        $algorithm = $this->wired->named($name);
        if ($algorithm !== null) {
            return $algorithm;
        }
        if ($this->wired->algorithms === []) {
            throw new UnsupportedAlgorithmException(sprintf('This provider encrypts no payload; "%s" is not read.', $name));
        }

        throw new UnsupportedAlgorithmException(sprintf(
            'This provider does not encrypt a payload with "%s"; it encrypts with %s.',
            $name,
            implode(', ', $this->wired->names()),
        ));
    }
}
