<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Event;

use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

/**
 * A person downloaded the archive they had asked for (issue #453). The act that matters most of the three:
 * a file holding everything this provider knows of somebody left it, and the journal says when and from
 * where.
 *
 * It carries the identifier and never the document, for the reason PersonAskedForTheirArchive gives.
 */
final readonly class PersonTookTheirArchive implements ConcernsAPersonInterface
{
    /**
     * The name of the act, which is what the journal writes down (issue #454).
     */
    public const string NAME = 'archive.taken';

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
