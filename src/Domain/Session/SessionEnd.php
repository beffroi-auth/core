<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Session;

/**
 * Who or what ended a session.
 */
enum SessionEnd: string
{
    case User = 'user';
    case Administrator = 'administrator';
    case Expiry = 'expiry';
    case RelyingParty = 'relying_party';
    case Security = 'security';
}
