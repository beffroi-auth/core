<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Credential\BreachCount;
use Beffroi\Core\Domain\Credential\PasswordDigest;
use Beffroi\Core\Exception\BreachLookupFailedException;
use SensitiveParameter;

/**
 * NIST SP 800-63B §3.1.1.2
 *
 * Only a digest crosses it: an implementation sends out at most its prefix and compares locally.
 */
interface BreachedPasswordsInterface
{
    public function looksUp(): bool;

    /**
     * @throws BreachLookupFailedException
     */
    public function timesSeen(#[SensitiveParameter] PasswordDigest $digest): BreachCount;
}
