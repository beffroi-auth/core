<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

/**
 * A person asked for everything this provider holds about them (RGPD article 15, issue #453). What builds
 * the archive listens for the message of the request; this says the act happened, and the journal writes
 * it down.
 *
 * It carries the identifier of the archive and **never the archive**: an event is written to a broker, and
 * a document holding in the clear everything this provider knows of a person is the one copy of it there
 * must not be.
 */
final readonly class PersonAskedForTheirArchive implements ConcernsAPersonInterface
{
    /**
     * The name of the act, which is what the journal writes down (issue #454).
     */
    public const string NAME = 'archive.asked';

    public function __construct(
        public DateTimeImmutable $occurredAt,
        public Tenant $tenant,
        public Uuid $archiveId,
        public Subject $person,
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
