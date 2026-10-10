<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Null;

use Beffroi\Core\Domain\Credential\BreachCount;
use Beffroi\Core\Domain\Credential\PasswordDigest;
use Beffroi\Core\Port\BreachedPasswordsInterface;
use SensitiveParameter;

/**
 * No list, no lookup: a password is posed on its own rules and nothing leaves. What whoever wires the core
 * by hand gets until they name a list.
 */
final class NullBreachedPasswords implements BreachedPasswordsInterface
{
    public function looksUp(): bool
    {
        return false;
    }

    public function timesSeen(#[SensitiveParameter] PasswordDigest $digest): BreachCount
    {
        return BreachCount::nothingAsked();
    }
}
