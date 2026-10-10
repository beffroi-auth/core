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
 * The password already posed, looked up and marked: a password that passed every rule the day it was
 * written turns up in a corpus published a month later, and without this nothing would ever look at it
 * again.
 *
 * **It is not BreachedPasswordRefusal.** That one refuses a password nobody holds yet and answers the
 * person posing it; this one is handed a password somebody already signs in with, so there is nothing to
 * refuse and the answer is a mark on the credential, read later on the pages of their account.
 *
 * **It is not on the login path.** A login keeps no third party on it, which is what "castor login-cost"
 * measures; this runs from a lane, handed the digest the request computed while the password was in
 * memory, and the mark it writes outlives that request.
 *
 * **An unknown leaves the credential alone.** A list that could not be asked marks nothing, not even as
 * checked, because nothing was: the credential stays due and the next login past the delay asks again.
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
     * @throws BreachLookupFailedException when the list could not be asked, which the lane tries again on
     *                                     the patience of its transport
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
