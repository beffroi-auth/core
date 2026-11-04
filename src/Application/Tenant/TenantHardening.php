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
 * Adopts the floor of a tenant's logins and forgets the credentials it forbids.
 *
 * The floor is written before the sweep, so that an interruption leaves a tenant refusing what it forbids.
 * A password already held is not swept for its length: its hash does not tell it, and NIST SP 800-63B
 * §3.1.1.2 asks for no change without evidence of compromise.
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
     * @return int how many credentials the floor forbade and this forgot
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

    private function isForbidden(Credential $credential, AuthenticationPredicateInterface $floor): bool
    {
        $methods = $credential instanceof ProvingCredentialInterface ? $credential->provableMethods() : [];

        return $methods !== [] && ! $floor->admits($methods);
    }
}
