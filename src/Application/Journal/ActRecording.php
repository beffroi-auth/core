<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Journal;

use Beffroi\Core\Application\Session\ProvenancePlace;
use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Domain\Journal\JournalEntry;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Port\JournalInterface;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

/**
 * Writes one act down (issue #454). It takes the facts and not the event: which event means which act,
 * and which of them names a person or a client, is read where both packages are visible at once, and that
 * is the integration (WriteTheActDownHandler).
 *
 * It draws the identifier, so nothing above it decides what a row is called: a UUIDv7, which sorts
 * chronologically, as the two use cases of the module that draw one do.
 *
 * It says where the act came from and not only which address reached this provider (issue #473): the
 * place is found here because this already runs on the lane the events travel, so no second message and
 * no second pass over the journal is needed for a row to say "from where" the day it is written.
 */
final readonly class ActRecording
{
    public function __construct(
        private JournalInterface $journal,
        private ProvenancePlace $place,
    ) {
    }

    public function record(
        Tenant $tenant,
        string $act,
        DateTimeImmutable $occurredAt,
        RequestContext $context,
        ?Subject $subject = null,
        ?string $client = null,
    ): void {
        $context = $context->withLocation($this->place->of($context));
        $this->journal->record(new JournalEntry(Uuid::v7(), $tenant, $act, $occurredAt, $context, $subject, $client));
    }
}
