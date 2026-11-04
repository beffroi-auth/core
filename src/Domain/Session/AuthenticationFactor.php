<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Session;

/**
 * NIST SP 800-63B §2
 */
enum AuthenticationFactor: string
{
    case Knowledge = 'knowledge';
    case Possession = 'possession';
    case Inherence = 'inherence';
}
