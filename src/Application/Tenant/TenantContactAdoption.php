<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Tenant;

use Beffroi\Core\Domain\Event\TenantContactChanged;
use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Domain\Tenant\TenantContact;
use Beffroi\Core\Port\TenantRepositoryInterface;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * NIST SP 800-63B-4 §4.6
 */
final readonly class TenantContactAdoption
{
    public function __construct(
        private TenantRepositoryInterface $tenants,
        private ClockInterface $clock,
        private EventDispatcherInterface $events,
    ) {
    }

    /**
     * The act names the administrator of the tenant who changed it (decision 0038); the contact held already is no act.
     */
    public function adoptByAnAdministrator(Tenant $tenant, ?TenantContact $contact, Subject $administrator, RequestContext $context): void
    {
        if ($contact?->equals($tenant->contact) ?? $tenant->contact === null) {
            return;
        }
        $tenant->adoptContact($contact);
        $this->tenants->save($tenant);
        $this->events->dispatch(new TenantContactChanged($tenant, $this->clock->now(), $context, $administrator));
    }
}
