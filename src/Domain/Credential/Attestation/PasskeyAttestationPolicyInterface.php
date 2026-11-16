<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential\Attestation;

use Beffroi\Core\Domain\Algorithm\NamedAlgorithmInterface;
use Beffroi\Core\Domain\Tenant\PasskeyAttestation;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Exception\PasskeyAttestationRefusedException;
use Beffroi\Core\Exception\PasskeyCeremonyFailedException;
use DateTimeImmutable;

/**
 * WebAuthn Level 3 §5.4.7, §7.1 steps 19 to 22
 */
interface PasskeyAttestationPolicyInterface extends NamedAlgorithmInterface
{
    /**
     * WebAuthn Level 3 §5.4.7
     *
     * @return non-empty-string
     */
    public function conveyancePreference(): string;

    /**
     * @throws InvalidArgumentException when the tenant names what this policy does not read, or not what it needs
     */
    public function assertReads(PasskeyAttestation $attestation): void;

    /**
     * @throws PasskeyAttestationRefusedException
     * @throws PasskeyCeremonyFailedException when the attestation does not hold
     */
    public function judge(PasskeyAttestation $attestation, AttestationInterface $seen, DateTimeImmutable $now): AttestationEvidence;
}
