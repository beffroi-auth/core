<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use Beffroi\Core\Domain\Credential\Attestation\ModelProof;
use Symfony\Component\Uid\Uuid;

/**
 * WebAuthn Level 3 §6
 */
interface ModelDeclaringCredentialInterface
{
    public Uuid $aaguid { get; }

    public ModelProof $modelProof { get; }

    public ?string $serialNumber { get; }

    public bool $underSecurityAlert { get; }
}
