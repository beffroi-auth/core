<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Identity;

enum MailEncryptionRefusal: string
{
    case Unreadable = 'unreadable';

    case NotForEncryption = 'not_for_encryption';

    case NotValidNow = 'not_valid_now';

    case AnotherAddress = 'another_address';

    case NoVerifiedAddress = 'no_verified_address';
}
