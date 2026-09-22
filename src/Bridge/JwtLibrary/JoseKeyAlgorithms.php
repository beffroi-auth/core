<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\JwtLibrary;

use Heidi\Core\Exception\UnsupportedAlgorithmException;
use Heidi\Core\Port\KeyAlgorithmsInterface;

/**
 * Every algorithm a key pair may be generated for, the two registries of one use read as one: what the
 * key commands resolve a "--algorithm" against and what reads a stored key back. It holds no list of its
 * own, so a wiring is written once and the three views of it cannot disagree (issue #98).
 */
final readonly class JoseKeyAlgorithms implements KeyAlgorithmsInterface
{
    public function __construct(
        private JoseSignatureAlgorithms $signature,
        private JoseKeyEncryptionAlgorithms $keyEncryption,
    ) {
    }

    /**
     * @return list<JoseKeyAlgorithmInterface>
     */
    public function all(): array
    {
        return [...$this->signature->all(), ...$this->keyEncryption->all()];
    }

    public function named(string $name): JoseKeyAlgorithmInterface
    {
        try {
            return $this->signature->named($name);
        } catch (UnsupportedAlgorithmException $signatureFailure) {
            try {
                return $this->keyEncryption->named($name);
            } catch (UnsupportedAlgorithmException $encryptionFailure) {
                throw new UnsupportedAlgorithmException($signatureFailure->getMessage() . ' ' . $encryptionFailure->getMessage(), 0, $encryptionFailure);
            }
        }
    }
}
