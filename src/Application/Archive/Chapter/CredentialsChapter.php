<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Archive\Chapter;

use Beffroi\Core\Domain\Archive\ArchivedFacts;
use Beffroi\Core\Domain\Credential\ExternallyIdentifiedCredentialInterface;
use Beffroi\Core\Domain\Credential\RevocableCredentialInterface;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Port\ArchiveChapterInterface;
use Beffroi\Core\Port\CredentialRepositoryInterface;

/**
 * Names every field it writes, so that no secret of a credential reaches the file.
 */
final readonly class CredentialsChapter implements ArchiveChapterInterface
{
    public function __construct(
        private CredentialRepositoryInterface $credentials,
    ) {
    }

    public function name(): string
    {
        return 'credentials';
    }

    public function of(User $user): array
    {
        $lines = [];
        foreach ($this->credentials->findByUser($user) as $credential) {
            $lines[] = [
                'kind' => $credential->kind(),
                'id' => $credential->id->toRfc4122(),
                'name' => $credential->label(),
                'external_id' => $credential instanceof ExternallyIdentifiedCredentialInterface ? ArchivedFacts::octets($credential->externalId) : null,
                'created_at' => ArchivedFacts::moment($credential->createdAt),
                'last_used_at' => ArchivedFacts::moment($credential->lastUsedAt),
                'revoked_at' => $credential instanceof RevocableCredentialInterface ? ArchivedFacts::moment($credential->revokedAt) : null,
            ];
        }

        return $lines;
    }
}
