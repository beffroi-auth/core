<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

/**
 * A person had this provider drop the archive it had built for them (issue #453). The third act of a file
 * that holds everything this provider knows of somebody: they asked for it, they took it, and they asked
 * that none of it be kept until the date it would have gone away on by itself.
 *
 * The identifier is all that is left of the archive once this is written, the row being gone, and that is
 * the point: what a reader of the journal needs is that the file of that request exists nowhere any more.
 */
final readonly class PersonDroppedTheirArchive implements ConcernsAPersonInterface
{
    /**
     * The name of the act, which is what the journal writes down (issue #454).
     */
    public const string NAME = 'archive.dropped';

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
