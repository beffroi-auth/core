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
 * The attempt is counted as soon as the user is named, before anything is verified.
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
     * @throws AuthenticationFailedException
     */
    public function verify(
        Tenant $tenant,
        string $identifier,
        #[SensitiveParameter]
        string $password,
        RequestContext $context,
    ): PasswordCredential {
        // No decoy hash here: this refusal says nothing of the account, and an Argon2id per request would be a
        // cost anybody could ask this provider to pay.
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

        // After the password on purpose: a disabled account answers like a wrong password.
        if (! $user->isEnabled()) {
            throw $this->refusals->refuse($tenant, $context, AuthenticationFailureReason::DisabledUser, AuthenticationMethod::password(), $identifier, $user);
        }

        // Also after the password: the page offers a federated person no password, but a forged post gets here.
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
