<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;

/**
 * An act of the tenant and of no person.
 */
final readonly class TenantContactChanged implements NamesItsAuthorInterface
{
    public const string NAME = 'tenant.contact_changed';

    /**
     * @param Subject $administrator the administrator of the tenant who changed it, by the subject of their account
     */
    public function __construct(
        public Tenant $tenant,
        public DateTimeImmutable $occurredAt,
        public RequestContext $context,
        public Subject $administrator,
    ) {
    }

    public function name(): string
    {
        return self::NAME;
    }

    public function author(): Subject
    {
        return $this->administrator;
    }
}
