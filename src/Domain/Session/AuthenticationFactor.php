<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Session;

/**
 * Something the user knows, has, or is (NIST SP 800-63B §2). Two proofs of the same kind are one factor.
 */
enum AuthenticationFactor: string
{
    case Knowledge = 'knowledge';
    case Possession = 'possession';
    case Inherence = 'inherence';
}
