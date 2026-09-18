<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Key;

/**
 * Why a key left the JWK Set, for the audit trail (architecture §3.3). Due: its retirement date passed,
 * every JWS it signed has expired. Forced: an operator retired it inside its window, knowingly. Compromised:
 * a rotation replaced it at once, with no window, because its private half may be in other hands.
 */
enum RetirementReason: string
{
    case Due = 'due';
    case Forced = 'forced';
    case Compromised = 'compromised';
}
