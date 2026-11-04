<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Session;

/**
 * Who or what ended a session; reaching its expiry ends none, so no case names it.
 */
enum SessionEnd: string
{
    case User = 'user';
    case Administrator = 'administrator';
    case RelyingParty = 'relying_party';
    case Security = 'security';
}
