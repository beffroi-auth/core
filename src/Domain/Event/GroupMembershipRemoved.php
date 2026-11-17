<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;

final readonly class GroupMembershipRemoved implements ConcernsAPersonInterface, NamesItsAuthorInterface
{
    public const string NAME = 'group_membership.removed';

    /**
     * @param string       $group         the name of the group, which names an organisational unit and not a person
     * @param Subject|null $administrator the administrator of the tenant who took them out of the group, by the subject of their account; null when the operator of the instance did
     */
    public function __construct(
        public Tenant $tenant,
        public Subject $person,
        public string $group,
        public DateTimeImmutable $occurredAt,
        public RequestContext $context,
        public ?Subject $administrator = null,
    ) {
    }

    public function name(): string
    {
        return self::NAME;
    }

    public function subject(): Subject
    {
        return $this->person;
    }

    public function author(): ?Subject
    {
        return $this->administrator;
    }
}
