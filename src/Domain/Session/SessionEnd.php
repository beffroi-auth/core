<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Session;

/**
 * Who or what ended a session. Reaching its expiry ends none (decision 0031): a session past its expiry is no
 * longer active, and nothing is announced or revoked for it, so no case names the expiry.
 */
enum SessionEnd: string
{
    case User = 'user';
    case Administrator = 'administrator';
    case RelyingParty = 'relying_party';
    case Security = 'security';
}
