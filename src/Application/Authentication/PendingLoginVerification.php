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

/**
 * What a login held between its two steps still is (architecture §3.2, issue #130): the user it names, in
 * this tenant, for a login that has not run out of time. Both are refusals of the second step, announced
 * here and nowhere else, so that the code page of the firewall and the use case of the core refuse the
 * same two things with the same reason and write the same act.
 *
 * A login past its lifetime is refused before the code is read, because a code typed late is not what was
 * wrong, and so is a login of a person of a federated domain (decision 0029); a login naming somebody this provider no longer serves names them by their identifier, there
 * being no user to name.
 */
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
     * The user the held login names.
     *
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
        // a login held before the tenant federated the domain of that person, or before their address moved
        // into it, completes nothing: they sign in through their provider and through nothing else (decision
        // 0029), and the code is not read
        if (! $this->federated->admitsLocalCredentials($user)) {
            throw $this->refusals->refuse($tenant, $context, AuthenticationFailureReason::FederatedDomain, TotpCredential::method(), $user->username, $user);
        }

        return $user;
    }
}
