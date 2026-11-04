<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

/**
 * NIST SP 800-63B §3.1.1.2
 */
enum PasswordRefusal: string
{
    case TooShort = 'too_short';

    case TooLong = 'too_long';

    case TooWeak = 'too_weak';

    case Breached = 'breached';

    case NotChecked = 'not_checked';
}
