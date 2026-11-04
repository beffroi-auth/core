<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Authentication;

use Beffroi\Core\Application\Identity\FederatedDomains;
use Beffroi\Core\Domain\Credential\TotpCredential;
use Beffroi\Core\Domain\Event\AuthenticationFailureReason;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Session\PendingLogin;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Port\UserRepositoryInterface;
use Psr\Clock\ClockInterface;

final readonly class PendingLoginVerification
{
    public function __construct(
        private UserRepositoryInterface $users,
        private AuthenticationRefusal $refusals,
        private ClockInterface $clock,
        private FederatedDomains $federated,
    ) {
    }

    /**
     * @throws AuthenticationFailedException
     */
    public function verify(Tenant $tenant, PendingLogin $login, RequestContext $context): User
    {
        $user = $this->users->find($login->userId);
        if ($user === null || ! $user->tenant->equals($tenant)) {
            throw $this->refusals->refuse(
                $tenant,
                $context,
                AuthenticationFailureReason::UnknownUser,
                TotpCredential::method(),
                $login->userId->toRfc4122(),
                null,
            );
        }
        if ($login->isExpired($this->clock->now())) {
            throw $this->refusals->refuse($tenant, $context, AuthenticationFailureReason::ExpiredLogin, TotpCredential::method(), $user->username, $user);
        }
        // The domain may have been federated since the first step: such a login completes nothing.
        if (! $this->federated->admitsLocalCredentials($user)) {
            throw $this->refusals->refuse($tenant, $context, AuthenticationFailureReason::FederatedDomain, TotpCredential::method(), $user->username, $user);
        }

        return $user;
    }
}
