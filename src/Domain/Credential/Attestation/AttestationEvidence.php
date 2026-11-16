<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential\Attestation;

use Beffroi\Core\Exception\InvalidArgumentException;
use function preg_match;

/**
 * What the enrolment of a passkey proved of its authenticator. The certificates are never kept.
 */
final readonly class AttestationEvidence
{
    /**
     * @param string|null $serialNumber WebAuthn Level 3 §8.2.2, in lowercase hexadecimal
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        public ModelProof $model,
        public ?string $serialNumber = null,
        public bool $underSecurityAlert = false,
    ) {
        if ($serialNumber === null) {
            return;
        }
        // WebAuthn Level 3 §8.2.2: "This extension MUST NOT be present in non-enterprise attestations."
        if ($model !== ModelProof::Enterprise) {
            throw new InvalidArgumentException('Only an enterprise attestation carries the serial number of its authenticator (WebAuthn Level 3 §8.2.2).');
        }
        if (preg_match('/^(?:[0-9a-f]{2})+$/D', $serialNumber) !== 1) {
            throw new InvalidArgumentException('The serial number of an authenticator is an octet string, written in lowercase hexadecimal.');
        }
    }

    public static function declared(bool $underSecurityAlert = false): self
    {
        return new self(ModelProof::Declared, underSecurityAlert: $underSecurityAlert);
    }

    public static function verified(bool $underSecurityAlert = false): self
    {
        return new self(ModelProof::Verified, underSecurityAlert: $underSecurityAlert);
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function enterprise(?string $serialNumber, bool $underSecurityAlert = false): self
    {
        return new self(ModelProof::Enterprise, $serialNumber, $underSecurityAlert);
    }
}
