<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;

/**
 * Never carries what the provider said of the person, which stays sealed in their row.
 */
final readonly class PersonProvisioned implements ConcernsAPersonInterface
{
    public const string NAME = 'person.provisioned';

    public function __construct(
        public DateTimeImmutable $occurredAt,
        public Tenant $tenant,
        public Subject $person,
        public string $provider,
        public RequestContext $context,
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
}
