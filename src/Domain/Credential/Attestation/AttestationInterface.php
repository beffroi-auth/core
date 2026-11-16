<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential\Attestation;

use Beffroi\Core\Domain\Tenant\AttestationRoot;
use Beffroi\Core\Exception\PasskeyCeremonyFailedException;
use Symfony\Component\Uid\Uuid;

/**
 * An attestation the ceremony verified, signature and certificate requirements included, which a policy examines.
 *
 * WebAuthn Level 3 §6.5, §7.1 steps 19 to 22
 */
interface AttestationInterface
{
    public Uuid $aaguid { get; }

    /**
     * WebAuthn Level 3 §7.1 step 22, FIDO Metadata Service v3.1.1 §3.1.3
     *
     * @throws PasskeyCeremonyFailedException when the metadata know the AAGUID and the attestation does not hold under them
     */
    public function provesTheModel(): bool;

    /**
     * @param list<AttestationRoot> $roots
     */
    public function chainsUpTo(array $roots): bool;

    /**
     * WebAuthn Level 3 §8.2.2, in hexadecimal
     *
     * @throws PasskeyCeremonyFailedException when the extension is marked critical or is no OCTET STRING
     */
    public function serialNumber(): ?string;

    /**
     * FIDO Metadata Service v3.1.1 §3.1.4.2
     */
    public function isUnderSecurityAlert(): bool;
}
