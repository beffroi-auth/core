<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Archive\Chapter;

use Beffroi\Core\Domain\Archive\ArchivedFacts;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Port\ArchiveChapterInterface;
use Beffroi\Core\Port\LinkedIdentitiesOfAPersonInterface;

/**
 * The identities a person has at upstream providers, in the archive they ask for (issue #453): which
 * provider, the subject it gave them, and the last snapshot of claims it sent.
 *
 * The claims are in the file because they are what this provider holds of that person, received from
 * somewhere else: a person who asks what is held about them is asking that too, and an answer that left
 * out what a federation sent would be an answer about our own forms.
 */
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
