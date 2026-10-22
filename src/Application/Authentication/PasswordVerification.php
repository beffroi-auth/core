<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Authentication;

use Beffroi\Core\Application\Credential\ActivePassword;
use Beffroi\Core\Application\Identity\FederatedDomains;
use Beffroi\Core\Domain\Credential\PasswordCredential;
use Beffroi\Core\Domain\Event\AuthenticationFailureReason;
use Beffroi\Core\Domain\Event\PasswordRehashed;
use Beffroi\Core\Domain\Event\PasswordUsed;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\AuthenticationMethod;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Port\CredentialRepositoryInterface;
use Beffroi\Core\Port\PasswordAttemptLimiterInterface;
use Beffroi\Core\Port\PasswordHasherInterface;
use Beffroi\Core\Port\UserRepositoryInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use SensitiveParameter;
use function trim;

/**
 * What a password proves, and the whole order of proving it (architecture §3.2, issue #130): the tenant is
 * asked whether a password proves anything here at all, the user is named, the attempts on them are
 * counted, the hash is verified, a disabled account and a person of a federated domain (decision 0029) are
 * refused after the password, the count is emptied,
 * the credential is dated and rehashed if the policy has moved, and the act of the password is announced. What the caller does with the proof, open a session, hold the login
 * until a code, hand a passport to a firewall, is the caller's, as it is for a code (TotpAuthentication).
 *
 * **It is the one order of a login by password**, and both paths of this provider play it: the use case
 * (PasswordAuthenticator) and the firewall of the bundle, which hands the request here rather than
 * verifying a hash of its own. A rule that lived in one of the two copies held on one of the two paths,
 * and the drift was not hypothetical: the refusals of one were announced and the refusals of the other
 * were not.
 *
 * **The attempts are counted as soon as the user is named and before anything is verified**, because a
 * password is guessed one attempt at a time; a refused attempt still pays a hash (DecoyHash), so that the
 * refusal does not say whether the user exists; a correct password empties the count.
 *
 * Every refusal is announced with the request context (AuthenticationRefusal), and so is the password
 * accepted (PasswordUsed, issue #571): the journal of a person says how somebody got in and not only that
 * they did.
 */
final readonly class PasswordVerification
{
    public function __construct(
        private UserRepositoryInterface $users,
        private CredentialRepositoryInterface $credentials,
        private ActivePassword $activePassword,
        private PasswordHasherInterface $hasher,
        private DecoyHash $decoy,
        private PasswordAttemptLimiterInterface $attempts,
        private AuthenticationRefusal $refusals,
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
        private FederatedDomains $federated,
    ) {
    }

    /**
     * The credential the password proved, which names the user it belongs to.
     *
     * @throws AuthenticationFailedException
     */
    public function verify(
        Tenant $tenant,
        string $identifier,
        #[SensitiveParameter]
        string $password,
        RequestContext $context,
    ): PasswordCredential {
        // the first step of the order (issue #327): a tenant whose floor reads nothing a password proves
        // holds none, so every password is refused the same way, before anybody is named. It pays no hash
        // where the refusals below pay one: the answer does not depend on the account, so there is nothing
        // for a clock to tell, and an Argon2id per request would be a cost anybody could ask this provider
        // to pay.
        if (! $tenant->loginFloor->admits([PasswordCredential::method()])) {
            throw $this->refusals->refuse(
                $tenant,
                $context,
                AuthenticationFailureReason::BelowTheFloor,
                AuthenticationMethod::password(),
                $identifier,
                null,
            );
        }

        $user = $this->users->findByUsername($tenant, trim($identifier));
        if ($user === null) {
            throw $this->refuseAtTheCostOfAPassword($tenant, $context, AuthenticationFailureReason::UnknownUser, $identifier, $password, null);
        }

        if (! $this->attempts->attempt($user)) {
            throw $this->refuseAtTheCostOfAPassword($tenant, $context, AuthenticationFailureReason::TooManyAttempts, $identifier, $password, $user);
        }

        $credential = $this->activePassword->of($user);
        if ($credential === null) {
            throw $this->refuseAtTheCostOfAPassword($tenant, $context, AuthenticationFailureReason::NoSuchCredential, $identifier, $password, $user);
        }

        if (! $this->hasher->verify($credential->hash, $password)) {
            throw $this->refusals->refuse(
                $tenant,
                $context,
                AuthenticationFailureReason::InvalidCredential,
                AuthenticationMethod::password(),
                $identifier,
                $user,
            );
        }

        // after the password on purpose: a disabled account answers like a wrong password
        if (! $user->isEnabled()) {
            throw $this->refusals->refuse($tenant, $context, AuthenticationFailureReason::DisabledUser, AuthenticationMethod::password(), $identifier, $user);
        }

        // and so does a person of a federated domain, who signs in through their provider and through nothing
        // else (decision 0029): the page offers them no password, and a post written by hand is refused here
        if (! $this->federated->admitsLocalCredentials($user)) {
            throw $this->refusals->refuse(
                $tenant,
                $context,
                AuthenticationFailureReason::FederatedDomain,
                AuthenticationMethod::password(),
                $identifier,
                $user,
            );
        }

        $this->attempts->reset($user);
        $now = $this->clock->now();
        $credential->recordUse($now);
        if ($this->hasher->needsRehash($credential->hash)) {
            $credential->rehash($this->hasher->hash($password), $now);
            $this->credentials->save($credential);
            $this->events->dispatch(new PasswordRehashed($credential, $now, $context));
        } else {
            $this->credentials->save($credential);
        }
        $this->events->dispatch(new PasswordUsed($credential, $now, $context));

        return $credential;
    }

    /**
     * A refusal reached before any hash of the user could be verified still pays for one (DecoyHash,
     * decision 0006): what is refused before the password was read must cost what a wrong password
     * costs, or the time says whether the account exists. The two are one gesture here, so that a
     * refusal added later cannot be the one that forgets to pay.
     */
    private function refuseAtTheCostOfAPassword(
        Tenant $tenant,
        RequestContext $context,
        AuthenticationFailureReason $reason,
        string $identifier,
        #[SensitiveParameter]
        string $password,
        ?User $user,
    ): AuthenticationFailedException {
        $this->decoy->verify($password);

        return $this->refusals->refuse($tenant, $context, $reason, AuthenticationMethod::password(), $identifier, $user);
    }
}
