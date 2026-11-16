<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential\Attestation;

enum PasskeyAttestationRefusal: string
{
    case OrganisationKeysOnly = 'organisation_keys_only';

    /**
     * FIDO Metadata Service v3.1.1 §3.1.4.2
     */
    case ModelUnderSecurityAlert = 'model_under_security_alert';
}
