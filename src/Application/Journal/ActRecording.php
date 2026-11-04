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
        ?Subject $author = null,
    ): void {
        $context = $context->withLocation($this->place->of($context));
        $this->journal->record(new JournalEntry(Uuid::v7(), $tenant, $act, $occurredAt, $context, $subject, $client, $author));
    }
}
