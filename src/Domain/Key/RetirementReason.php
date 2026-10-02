<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Key;

/**
 * Why a key left the JWK Set, for the audit trail (architecture §3.3).
 */
enum RetirementReason: string
{
    case Due = 'due';
    case Forced = 'forced';
    case Compromised = 'compromised';
}
