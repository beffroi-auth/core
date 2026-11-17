<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;

/**
 * Carries the names of what changed, never a value: a personal datum stays in the row of its person, and the history of
 * the person holds what it said before.
 */
final readonly class PersonDescribed implements ConcernsAPersonInterface, NamesItsAuthorInterface
{
    public const string NAME = 'person.described';

    /**
     * @param list<string> $changed       the names the history of the person writes the changes under
     * @param Subject      $administrator the administrator of the tenant who described them, by the subject of their account
     */
    public function __construct(
        public Tenant $tenant,
        public Subject $person,
        public array $changed,
        public DateTimeImmutable $occurredAt,
        public RequestContext $context,
        public Subject $administrator,
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

    public function author(): Subject
    {
        return $this->administrator;
    }
}
