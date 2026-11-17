<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;

final readonly class PersonEnabled implements ConcernsAPersonInterface, NamesItsAuthorInterface
{
    public const string NAME = 'person.enabled';

    /**
     * @param Subject|null $administrator the administrator of the tenant who enabled them, by the subject of their account; null when the operator of the instance did
     */
    public function __construct(
        public Tenant $tenant,
        public Subject $person,
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
