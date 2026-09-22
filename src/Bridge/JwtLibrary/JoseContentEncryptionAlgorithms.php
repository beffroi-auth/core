<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\JwtLibrary;

use function array_values;
use Heidi\Core\Exception\InvalidArgumentException;
use Heidi\Core\Port\ContentEncryptionAlgorithmsInterface;
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
}
