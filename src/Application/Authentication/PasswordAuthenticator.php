<?php

declare(strict_types=1);

namespace Heidi\Core\Application\Authentication;

use Heidi\Core\Domain\Credential\PasswordCredential;
use Heidi\Core\Domain\Event\AuthenticationFailed;
use Heidi\Core\Domain\Event\AuthenticationFailureReason;
use Heidi\Core\Domain\Event\PasswordRehashed;
use Heidi\Core\Domain\Identity\User;
use Heidi\Core\Domain\Session\AuthenticationMethod;
use Heidi\Core\Domain\Session\AuthenticationSession;
use Heidi\Core\Domain\Session\PendingLogin;
use Heidi\Core\Domain\Session\RequestContext;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Port\CredentialRepositoryInterface;
use Heidi\Core\Port\PasswordAttemptLimiterInterface;
use Heidi\Core\Port\PasswordHasherInterface;
use Heidi\Core\Port\UserRepositoryInterface;
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
            $this->decoy->verify($password);

            throw $this->failure($tenant, $context, AuthenticationFailureReason::UnknownUser, $identifier, null);
        }

        if (! $this->attempts->attempt($user)) {
            $this->decoy->verify($password);

            throw $this->failure($tenant, $context, AuthenticationFailureReason::TooManyAttempts, $identifier, $user);
        }

        $credential = $this->credentials->findByUser($user, PasswordCredential::class)[0] ?? null;
        if ($credential === null) {
            $this->decoy->verify($password);

            throw $this->failure($tenant, $context, AuthenticationFailureReason::NoSuchCredential, $identifier, $user);
        }

        if (! $this->hasher->verify($credential->hash, $password)) {
            throw $this->failure($tenant, $context, AuthenticationFailureReason::InvalidCredential, $identifier, $user);
        }

        // after the password on purpose: a disabled account answers like a wrong password
        if (! $user->isEnabled()) {
            throw $this->failure($tenant, $context, AuthenticationFailureReason::DisabledUser, $identifier, $user);
        }

        $this->attempts->reset($user);
        $now = $this->clock->now();
        $credential->markUsed($now);
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

    private function failure(
        Tenant $tenant,
        RequestContext $context,
        AuthenticationFailureReason $reason,
        string $identifier,
        ?User $user,
    ): AuthenticationFailedException {
        $this->events->dispatch(new AuthenticationFailed(
            $this->clock->now(),
            $tenant,
            $context,
            AuthenticationMethod::password(),
            $reason,
            $identifier,
            $user,
        ));

        return new AuthenticationFailedException($reason);
    }
}
