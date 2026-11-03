<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use Symfony\Component\Uid\Uuid;

/**
 * A credential whose authenticator declared its make and model when it was created: the AAGUID of WebAuthn
 * Level 3 §6, "a 128-bit identifier indicating the type (e.g. make and model) of the authenticator", which the
 * attested credential data of a passkey carries (§6.5.1). It is what the authenticator says of itself: the
 * provider asks for no attestation (§5.4.7, "none"), and §6 warns that the AAGUID "is not provably authentic
 * without attestation", so a page names the model as declared and never as proven (AuthenticatorModels).
 */
interface ModelDeclaringCredentialInterface
{
    public Uuid $aaguid { get; }
}
