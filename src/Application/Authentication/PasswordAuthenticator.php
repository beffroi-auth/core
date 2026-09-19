<?php

declare(strict_types=1);

namespace Heidi\Core\Application\Authentication;

use DateInterval;
use Heidi\Core\Domain\Credential\PasswordCredential;
use Heidi\Core\Domain\Credential\PasswordHash;
use Heidi\Core\Domain\Event\AuthenticationFailed;
use Heidi\Core\Domain\Event\AuthenticationFailureReason;
use Heidi\Core\Domain\Event\PasswordRehashed;
use Heidi\Core\Domain\Event\UserAuthenticated;
use Heidi\Core\Domain\Identity\User;
use Heidi\Core\Domain\Session\AuthenticationMethod;
use Heidi\Core\Domain\Session\AuthenticationProofs;
use Heidi\Core\Domain\Session\AuthenticationSession;
use Heidi\Core\Domain\Session\RequestContext;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Port\AuthenticationSessionRepositoryInterface;
use Heidi\Core\Port\CredentialRepositoryInterface;
use Heidi\Core\Port\PasswordHasherInterface;
use Heidi\Core\Port\UserRepositoryInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use SensitiveParameter;
use Symfony\Component\Uid\Uuid;
use function trim;

/**
 * Authenticates a user with a password and opens a session. Every outcome is an event carrying the request context.
 */
final class PasswordAuthenticator
{
    /**
     * Verified against when the user does not exist, so that a failure costs the same time either way.
     */
    private ?PasswordHash $decoyHash = null;

    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly CredentialRepositoryInterface $credentials,
        private readonly PasswordHasherInterface $hasher,
        private readonly AuthenticationSessionRepositoryInterface $sessions,
        private readonly ClockInterface $clock,
        private readonly EventDispatcherInterface $events,
        private readonly DateInterval $sessionLifetime = new DateInterval('PT12H'),
    ) {
    }

    /**
     * @throws AuthenticationFailedException
     */
    public function authenticate(
        Tenant $tenant,
        string $identifier,
        #[SensitiveParameter]
        string $password,
        RequestContext $context,
    ): AuthenticationSession {
        $user = $this->users->findByUsername($tenant, trim($identifier));
        if ($user === null) {
            $this->hasher->verify($this->decoyHash(), $password);

            throw $this->failure($tenant, $context, AuthenticationFailureReason::UnknownUser, $identifier, null);
        }

        $credential = $this->credentials->findByUser($user, PasswordCredential::class)[0] ?? null;
        if ($credential === null) {
            $this->hasher->verify($this->decoyHash(), $password);

            throw $this->failure($tenant, $context, AuthenticationFailureReason::NoSuchCredential, $identifier, $user);
        }

        if (! $this->hasher->verify($credential->hash, $password)) {
            throw $this->failure($tenant, $context, AuthenticationFailureReason::InvalidCredential, $identifier, $user);
        }

        // after the password on purpose: a disabled account answers like a wrong password
        if (! $user->isEnabled()) {
            throw $this->failure($tenant, $context, AuthenticationFailureReason::DisabledUser, $identifier, $user);
        }

        $now = $this->clock->now();
        $credential->markUsed($now);
        if ($this->hasher->needsRehash($credential->hash)) {
            $credential->rehash($this->hasher->hash($password), $now);
            $this->credentials->save($credential);
            $this->events->dispatch(new PasswordRehashed($credential, $now, $context));
        } else {
            $this->credentials->save($credential);
        }

        $session = new AuthenticationSession(
            Uuid::v7(),
            $tenant,
            $user,
            $now,
            AuthenticationProofs::of(AuthenticationMethod::password(), $now),
            $context,
            $now->add($this->sessionLifetime),
        );
        $this->sessions->save($session);
        $this->events->dispatch(new UserAuthenticated($session));

        return $session;
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

    private function decoyHash(): PasswordHash
    {
        return $this->decoyHash ??= $this->hasher->hash(Uuid::v4()->toRfc4122());
    }
}
