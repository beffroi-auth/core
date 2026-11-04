<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Null;

use Beffroi\Core\Domain\Credential\BreachCount;
use Beffroi\Core\Domain\Credential\PasswordDigest;
use Beffroi\Core\Port\BreachedPasswordsInterface;
use SensitiveParameter;

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
