<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Key;

/**
 * RFC 7517 §4.2, NIST SP 800-57 Part 1 Rev. 5 §5.2
 */
enum KeyUse: string
{
    case Signature = 'sig';

    case Encryption = 'enc';
}
