<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Tenant;

use Beffroi\Core\Domain\Credential\Credential;
use Beffroi\Core\Domain\Credential\ProvingCredentialInterface;
use Beffroi\Core\Domain\Event\CredentialForgotten;
use Beffroi\Core\Domain\Session\AuthenticationPredicateInterface;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Port\CanForgetCredentialsInterface;
use Beffroi\Core\Port\TenantRepositoryInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * The tenant names the floor of its logins, and what the floor forbids leaves the model (issue #327): a
 * tenant that moves to passkeys says so once, and the passwords, the one-time codes and the backup codes
 * of its users are not left dormant behind the refusal. Whoever administers the tenant calls this, from a
 * command or from the console, and both call it: the act is one use case and not two copies of a policy.
 *
 * **What the floor forbids is read off the floor** and never listed here: each credential says what one
 * authentication with it would prove (ProvingCredentialInterface) and the floor says whether it reads any
 * of those methods (AuthenticationPredicateInterface::admits()). A kind this provider gains is swept by the same
 * walk, with nothing to edit.
 *
 * **The floor is written first and the sweep follows**, so that an interruption leaves a tenant that
 * refuses what it forbids rather than a tenant that still admits it: the login is refused by the floor
 * from the first instant (SessionOpener), and running this again finishes the sweep. Lowering the floor
 * again gives nobody back what was forgotten; a password that went has to be posed again.
 */
final readonly class TenantHardening
{
    public function __construct(
        private TenantRepositoryInterface $tenants,
        private CanForgetCredentialsInterface $credentials,
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
    ) {
    }

    /**
     * @return int how many credentials the floor forbade and this forgot, zero when it forbade none
     */
    public function adopt(Tenant $tenant, AuthenticationPredicateInterface $floor, RequestContext $context): int
    {
        $tenant->adoptLoginFloor($floor);
        $this->tenants->save($tenant);
        $now = $this->clock->now();
        $forgotten = 0;
        foreach ($this->credentials->credentialsOf($tenant) as $credential) {
            if (! $this->isForbidden($credential, $floor)) {
                continue;
            }
            $this->credentials->forget($credential);
            $this->events->dispatch(new CredentialForgotten($credential, $now, $context));
            ++$forgotten;
        }

        return $forgotten;
    }

    /**
     * A credential that could prove nothing anyway, a revoked one, is not a credential the floor forbids:
     * it holds no way in, and the page of that person keeps naming it.
     */
    private function isForbidden(Credential $credential, AuthenticationPredicateInterface $floor): bool
    {
        $methods = $credential instanceof ProvingCredentialInterface ? $credential->provableMethods() : [];

        return $methods !== [] && ! $floor->admits($methods);
    }
}
