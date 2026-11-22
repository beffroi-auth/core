<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use Beffroi\Core\Domain\Notification\NotificationKindInterface;

/**
 * NIST SP 800-63B-4 §4.6
 *
 * No choice of the person narrows a change of the credentials of the account: every channel that carries it tells them.
 */
final readonly class CredentialChangeKind implements NotificationKindInterface
{
    public const string NAME = 'credential_change';

    public function name(): string
    {
        return self::NAME;
    }
}
