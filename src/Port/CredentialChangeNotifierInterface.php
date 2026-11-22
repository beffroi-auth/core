<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Credential\CredentialChange;

/**
 * NIST SP 800-63B-4 §4.6
 */
interface CredentialChangeNotifierInterface
{
    public function tell(CredentialChange $change): void;
}
