<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Archive\Chapter;

use Beffroi\Core\Domain\Archive\ArchivedFacts;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Port\ArchiveChapterInterface;
use Beffroi\Core\Port\LinkedIdentitiesOfAPersonInterface;

final readonly class LinkedIdentitiesChapter implements ArchiveChapterInterface
{
    public function __construct(
        private LinkedIdentitiesOfAPersonInterface $identities,
    ) {
    }

    public function name(): string
    {
        return 'linked_identities';
    }

    public function of(User $user): array
    {
        $lines = [];
        foreach ($this->identities->of($user) as $identity) {
            $lines[] = [
                'provider' => $identity->provider,
                'sub' => $identity->subject,
                'claims' => $identity->claims,
                'linked_at' => ArchivedFacts::moment($identity->linkedAt),
                'last_seen_at' => ArchivedFacts::moment($identity->lastSeenAt),
            ];
        }

        return $lines;
    }
}
