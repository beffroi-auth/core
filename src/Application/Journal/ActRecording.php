<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Journal;

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
 */
final readonly class ActRecording
{
    public function __construct(
        private JournalInterface $journal,
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
        $this->journal->record(new JournalEntry(Uuid::v7(), $tenant, $act, $occurredAt, $context, $subject, $client));
    }
}
