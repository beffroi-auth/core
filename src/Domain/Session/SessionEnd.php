<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Session;

/**
 * Who or what ended a session. It matters for the user reading their history and for the audit trail.
 */
enum SessionEnd: string
{
    case User = 'user';
    case Administrator = 'administrator';
    case Expiry = 'expiry';
    case RelyingParty = 'relying_party';
    case Security = 'security';
}
