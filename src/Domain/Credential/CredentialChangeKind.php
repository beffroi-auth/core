<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use Beffroi\Core\Domain\Notification\NotificationKindInterface;

/**
 * NIST SP 800-63B-4 §4.6
 *
 * A change of the credentials of the account reaches every channel, whatever the person chose.
 */
final readonly class CredentialChangeKind implements NotificationKindInterface
{
    public const string NAME = 'credential_change';

    public function name(): string
    {
        return self::NAME;
    }
}
