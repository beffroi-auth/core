<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\JwtLibrary;

use function array_values;
use Heidi\Core\Exception\InvalidArgumentException;
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
     * @var list<JoseContentEncryptionAlgorithmInterface>
     */
    private array $algorithms;

    /**
     * @param iterable<JoseContentEncryptionAlgorithmInterface> $algorithms in the order they are wired, none of them twice
     */
    public function __construct(iterable $algorithms)
    {
        $wired = [];
        foreach ($algorithms as $algorithm) {
            $name = $algorithm->name();
            if (isset($wired[$name])) {
                throw new InvalidArgumentException(sprintf('The algorithm "%s" is wired twice.', $name));
            }
            $wired[$name] = $algorithm;
        }
        $this->algorithms = array_values($wired);
    }

    /**
     * @return list<JoseContentEncryptionAlgorithmInterface>
     */
    public function all(): array
    {
        return $this->algorithms;
    }

    public function named(string $name): JoseContentEncryptionAlgorithmInterface
    {
        foreach ($this->algorithms as $algorithm) {
            if ($algorithm->name() === $name) {
                return $algorithm;
            }
        }
        if ($this->algorithms === []) {
            throw new UnsupportedAlgorithmException(sprintf('This provider encrypts no payload; "%s" is not read.', $name));
        }

        throw new UnsupportedAlgorithmException(sprintf(
            'This provider does not encrypt a payload with "%s"; it encrypts with %s.',
            $name,
            implode(', ', $this->names()),
        ));
    }

    /**
     * @return list<non-empty-string>
     */
    private function names(): array
    {
        $names = [];
        foreach ($this->algorithms as $algorithm) {
            $names[] = $algorithm->name();
        }

        return $names;
    }
}
