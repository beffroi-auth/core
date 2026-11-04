<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Credential;

use Beffroi\Core\Domain\Credential\PasswordCredential;
use Beffroi\Core\Domain\Credential\PasswordDigest;
use Beffroi\Core\Exception\BreachLookupFailedException;
use Beffroi\Core\Port\BreachedPasswordsInterface;
use Beffroi\Core\Port\CredentialRepositoryInterface;
use Psr\Clock\ClockInterface;
use SensitiveParameter;

/**
 * A lookup that fails marks nothing, not even as checked: the next login past the delay asks again.
 */
final readonly class BreachedPasswordLookup
{
    public function __construct(
        private BreachedPasswordsInterface $breaches,
        private CredentialRepositoryInterface $credentials,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws BreachLookupFailedException
     */
    public function lookUp(PasswordCredential $credential, #[SensitiveParameter] PasswordDigest $digest): void
    {
        $count = $this->breaches->timesSeen($digest);
        if (! $count->wasAsked()) {
            return;
        }
        $now = $this->clock->now();
        if ($count->isBreached()) {
            $credential->foundInABreach($now);
        } else {
            $credential->notFoundInABreach($now);
        }
        $this->credentials->save($credential);
    }
}
