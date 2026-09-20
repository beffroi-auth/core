<?php

declare(strict_types=1);

namespace Heidi\Core\Application\Authentication;

use Heidi\Core\Domain\Credential\PasswordCredential;
use Heidi\Core\Domain\Credential\PasswordHash;
use Heidi\Core\Domain\Event\AuthenticationFailed;
use Heidi\Core\Domain\Event\AuthenticationFailureReason;
use Heidi\Core\Domain\Event\PasswordRehashed;
use Heidi\Core\Domain\Identity\User;
use Heidi\Core\Domain\Session\AuthenticationMethod;
use Heidi\Core\Domain\Session\AuthenticationSession;
use Heidi\Core\Domain\Session\RequestContext;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Port\CredentialRepositoryInterface;
use Heidi\Core\Port\PasswordAttemptLimiterInterface;
use Heidi\Core\Port\PasswordHasherInterface;
use Heidi\Core\Port\UserRepositoryInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use SensitiveParameter;
use Symfony\Component\Uid\Uuid;
use function trim;

/**
 * Authenticates a user with a password and opens a session through the SessionOpener. The attempts on a
 * user are counted as soon as the user is named and before anything is verified, because a password is
 * guessed one attempt at a time; a refused attempt still pays a hash, so that the refusal does not say
 * whether the user exists; a correct password empties the count. Every outcome is an event carrying the
 * request context.
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
        private readonly SessionOpener $opener,
        private readonly PasswordAttemptLimiterInterface $attempts,
        private readonly ClockInterface $clock,
        private readonly EventDispatcherInterface $events,
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

        if (! $this->attempts->attempt($user)) {
            $this->hasher->verify($this->decoyHash(), $password);

            throw $this->failure($tenant, $context, AuthenticationFailureReason::TooManyAttempts, $identifier, $user);
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

    private function decoyHash(): PasswordHash
    {
        return $this->decoyHash ??= $this->hasher->hash(Uuid::v4()->toRfc4122());
    }
}
