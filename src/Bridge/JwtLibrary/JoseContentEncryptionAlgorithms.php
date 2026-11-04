<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

use Beffroi\Core\Bridge\Algorithm\WiredAlgorithms;
use Beffroi\Core\Port\ContentEncryptionAlgorithmsInterface;

final readonly class JoseContentEncryptionAlgorithms implements ContentEncryptionAlgorithmsInterface
{
    /**
     * @var WiredAlgorithms<JoseContentEncryptionAlgorithmInterface>
     */
    private WiredAlgorithms $wired;

    /**
     * @param iterable<JoseContentEncryptionAlgorithmInterface> $algorithms
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
