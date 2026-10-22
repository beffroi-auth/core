<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;

/**
 * An upstream provider created a person, at the first sign-in through it (`JustInTimeProvisioning`): an act
 * of their account, the first one, which they read in their own journal beside the sign-in that follows it.
 *
 * It carries the subject of the person and the name of the provider, which is what the act names, and
 * **never what the provider said of them**: the address and the claims live in the row of that person and in
 * the snapshot of their linked identity, sealed, and not in a copy a broker holds.
 */
final readonly class PersonProvisioned implements ConcernsAPersonInterface
{
    /**
     * The name of the act, which is what the journal writes down (issue #454).
     */
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
