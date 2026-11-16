<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Tenant;

use Beffroi\Core\Exception\InvalidArgumentException;
use function preg_match;

/**
 * A certificate authority the tenant pins, which AttestationRootReaderInterface reads: the domain sees its PEM only.
 */
final readonly class AttestationRoot
{
    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        public string $pem,
    ) {
        if (preg_match('/^-----BEGIN CERTIFICATE-----\r?\n[A-Za-z0-9+\/=\r\n]+-----END CERTIFICATE-----\r?\n?$/D', $pem) !== 1) {
            throw new InvalidArgumentException('A root of passkey attestations is one certificate in PEM.');
        }
    }

    public function equals(self $other): bool
    {
        return $this->pem === $other->pem;
    }
}
