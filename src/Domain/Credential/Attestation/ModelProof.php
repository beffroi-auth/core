<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential\Attestation;

/**
 * WebAuthn Level 3 §6: "the AAGUID is not provably authentic without attestation".
 */
enum ModelProof: string
{
    case Declared = 'declared';

    case Verified = 'verified';

    case Enterprise = 'enterprise';
}
