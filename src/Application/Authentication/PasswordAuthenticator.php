<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Authentication;

use Beffroi\Core\Domain\Credential\PasswordCredential;
use Beffroi\Core\Domain\Event\AuthenticationFailureReason;
use Beffroi\Core\Domain\Event\PasswordRehashed;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\AuthenticationMethod;
use Beffroi\Core\Domain\Session\AuthenticationSession;
use Beffroi\Core\Domain\Session\PendingLogin;
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
 * Authenticates a user with a password: the first step of a login, and the whole of it for a user who
 * holds no second factor. The attempts on a user are counted as soon as the user is named and before
 * anything is verified, because a password is guessed one attempt at a time; a refused attempt still
 * pays a hash (DecoyHash), so that the refusal does not say whether the user exists; a correct password
 * empties the count. A password alone signs nobody in who holds an active second factor
 * (SecondFactorRequirement, architecture §3.2): the login is then held as a PendingLogin, which
 * LoginCompletion turns into a session once the code passes; otherwise the session is opened here
 * through the SessionOpener. Every refusal is an event carrying the request context; a held login is not
 * an outcome yet and announces nothing.
 */
final readonly class PasswordAuthenticator
{
    public function __construct(
        private UserRepositoryInterface $users,
        private CredentialRepositoryInterface $credentials,
        private PasswordHasherInterface $hasher,
        private DecoyHash $decoy,
        private SessionOpener $opener,
        private PasswordAttemptLimiterInterface $attempts,
        private SecondFactorRequirement $secondFactor,
        private AuthenticationRefusal $refusals,
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
    ) {
    }

    /**
     * The session opened, or the login held until a code when the user holds a second factor.
     *
     * @throws AuthenticationFailedException
     */
    public function authenticate(
        Tenant $tenant,
        string $identifier,
        #[SensitiveParameter]
        string $password,
        RequestContext $context,
    ): AuthenticationSession|PendingLogin {
        $user = $this->users->findByUsername($tenant, trim($identifier));
        if ($user === null) {
            throw $this->refuseAtTheCostOfAPassword($tenant, $context, AuthenticationFailureReason::UnknownUser, $identifier, $password, null);
        }

        if (! $this->attempts->attempt($user)) {
            throw $this->refuseAtTheCostOfAPassword($tenant, $context, AuthenticationFailureReason::TooManyAttempts, $identifier, $password, $user);
        }

        $credential = $this->credentials->findByUser($user, PasswordCredential::class)[0] ?? null;
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
        if ($this->secondFactor->requiredFor($user)) {
            return PendingLogin::provenAt($user->id, [AuthenticationMethod::password()], $now);
        }

        return $this->opener->open($tenant, $user, [AuthenticationMethod::password()], $context);
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
