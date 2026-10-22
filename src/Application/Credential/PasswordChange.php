<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Credential;

use Beffroi\Core\Application\Identity\FederatedDomains;
use Beffroi\Core\Domain\Credential\PasswordChangeRefusal;
use Beffroi\Core\Domain\Credential\PasswordCredential;
use Beffroi\Core\Domain\Credential\PlainPassword;
use Beffroi\Core\Domain\Event\PasswordChanged;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Exception\LogicException;
use Beffroi\Core\Exception\PasswordChangeRefusedException;
use Beffroi\Core\Exception\PasswordRefusedException;
use Beffroi\Core\Port\CredentialRepositoryInterface;
use Beffroi\Core\Port\PasswordHasherInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use SensitiveParameter;

/**
 * A person replaces their own password, from a session they already hold (architecture §3.1). Not
 * UserProvisioner::setPassword(), which is an operator writing a password for somebody: here the person
 * proves the one in use before the one they pose is written.
 *
 * **The password in use is typed again and verified.** A session is not a proof that whoever holds it
 * knows the password, which is exactly what matters here: a session somebody else took over does not get
 * to replace the password it signed in with and lock its owner out.
 *
 * **What the guess costs is not counted against the login.** The attempts of a password login are counted
 * per user (PasswordAttemptLimiterInterface, the window being the bundle's: RateLimitedAttempts), and
 * counting these there would let whoever holds a session spend the budget of the person whose session it
 * is, which is the owner locked out by the form meant to protect them. What bounds a guess here is that
 * one has to be signed in as that person to make it.
 *
 * **What happens to the other sessions is the caller's policy**, as it is for every act on a credential:
 * this writes the password and says so.
 *
 * **What a list of compromised passwords says of the one posed is asked last**, after the two refusals
 * this provider pronounces on its own: a person who mistyped the password they use now is answered
 * without a prefix of anything leaving, and a password this provider will not hold is not sent out to be
 * counted either.
 *
 * A tenant that signs its users in without a password holds none to replace (issue #327), which is the
 * first thing this answers: the floor of the tenant is read before a row is, so a password a hardening
 * has not swept away yet is not replaced either.
 */
final readonly class PasswordChange
{
    public function __construct(
        private CredentialRepositoryInterface $credentials,
        private ActivePassword $activePassword,
        private PasswordHasherInterface $hasher,
        private BreachedPasswordRefusal $breaches,
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
        private FederatedDomains $federated,
    ) {
    }

    /**
     * @param string $inUse what was typed as the password in use, which is never what is stored
     * @param PlainPassword $posed what the person wants instead, built where what a password has to be is
     *                             checked
     *
     * @throws PasswordChangeRefusedException when the password in use was not typed, when the one posed is
     *                                        the one already held, when this person holds none, or when they
     *                                        are of a federated domain
     * @throws PasswordRefusedException when a list of compromised passwords counts the one posed, or could
     *                                 not be asked
     * @throws LogicException when no hasher of this deployment handles the algorithm the stored hash names
     */
    public function change(
        User $user,
        #[SensitiveParameter]
        string $inUse,
        #[SensitiveParameter]
        PlainPassword $posed,
        RequestContext $context,
    ): void {
        // a tenant that hardened holds no password of anybody (issue #327): the hardening forgot them, so
        // this is the row a store left behind, and the person is answered what they are told when they
        // hold none, which is what they do hold here
        if (! $user->tenant->loginFloor->admits([PasswordCredential::method()])) {
            throw new PasswordChangeRefusedException(
                PasswordChangeRefusal::NoPasswordHeld,
                'The tenant signs its users in without a password; there is nothing to replace.',
            );
        }
        // and a person of a federated domain holds none either, whatever a store kept of the time before (decision
        // 0029): replacing it would be posing a local credential for them
        if (! $this->federated->admitsLocalCredentials($user)) {
            throw new PasswordChangeRefusedException(
                PasswordChangeRefusal::FederatedDomain,
                'The person is of an e-mail domain the tenant federates; no password of theirs is replaced.',
            );
        }
        $credential = $this->activePassword->of($user);
        if ($credential === null) {
            throw new PasswordChangeRefusedException(
                PasswordChangeRefusal::NoPasswordHeld,
                'The user holds no password; there is nothing to replace.',
            );
        }
        if (! $this->hasher->verify($credential->hash, $inUse)) {
            throw new PasswordChangeRefusedException(
                PasswordChangeRefusal::WrongPassword,
                'What was typed is not the password in use.',
            );
        }
        if ($this->hasher->verify($credential->hash, $posed->value)) {
            throw new PasswordChangeRefusedException(
                PasswordChangeRefusal::SameAsBefore,
                'The password posed is the one already held.',
            );
        }
        // last of the refusals, because it is the only one that leaves this process: a person who typed
        // the wrong password in use, or the one they already hold, is told so without anybody being asked
        $count = $this->breaches->count($posed);
        $now = $this->clock->now();
        $credential->changePassword($this->hasher->hash($posed->value), $now);
        if ($count->wasAsked()) {
            $credential->notFoundInABreach($now);
        }
        $this->credentials->save($credential);
        $this->events->dispatch(new PasswordChanged($credential, $now, $context));
    }
}
