<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use Symfony\Component\Uid\Uuid;

/**
 * WebAuthn Level 3 §6
 *
 * The AAGUID is not provable without attestation, which the provider never asks for: a model is only declared.
 */
interface ModelDeclaringCredentialInterface
{
    public Uuid $aaguid { get; }
}
