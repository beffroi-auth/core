<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Credential;

use Beffroi\Core\Domain\Credential\BreachCount;
use Beffroi\Core\Domain\Credential\PasswordDigest;
use Beffroi\Core\Domain\Credential\PasswordRefusal;
use Beffroi\Core\Domain\Credential\PlainPassword;
use Beffroi\Core\Exception\BreachLookupFailedException;
use Beffroi\Core\Exception\PasswordRefusedException;
use Beffroi\Core\Port\BreachedPasswordsInterface;
use SensitiveParameter;

/**
 * NIST SP 800-63B §3.1.1.2
 */
final readonly class BreachedPasswordRefusal
{
    public function __construct(
        private BreachedPasswordsInterface $breaches,
    ) {
    }

    /**
     * @throws PasswordRefusedException when the list counts the password, or could not be asked
     */
    public function count(#[SensitiveParameter] PlainPassword $password): BreachCount
    {
        if (! $this->breaches->looksUp()) {
            return BreachCount::nothingAsked();
        }
        try {
            $count = $this->breaches->timesSeen(PasswordDigest::of($password->value));
        } catch (BreachLookupFailedException $failure) {
            throw new PasswordRefusedException(
                PasswordRefusal::NotChecked,
                'The list of compromised passwords could not be asked: the password is not written. Try again.',
                previous: $failure,
            );
        }
        if ($count->isBreached()) {
            throw new PasswordRefusedException(
                PasswordRefusal::Breached,
                'This password is in a list of compromised passwords: somebody else already holds it. Pose another one.',
            );
        }

        return $count;
    }
}
