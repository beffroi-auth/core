<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Session;

/**
 * The kind of factor an authentication method proves: something the user knows, has, or is (NIST SP
 * 800-63B §4, "factor"). Two proofs of the same kind, a password and a PIN, are one factor; a password
 * and a one-time password are two. What no kind covers (a location, a risk score, a presence check)
 * strengthens no class on its own.
 */
enum AuthenticationFactor: string
{
    case Knowledge = 'knowledge';
    case Possession = 'possession';
    case Inherence = 'inherence';
}
