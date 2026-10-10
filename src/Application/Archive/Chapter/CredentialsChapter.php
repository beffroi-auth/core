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
 * How a person proves who they are, in the archive they ask for (issue #453): one line per credential,
 * with its kind, what it is called, and the dates of its life.
 *
 * **Without their secrets, whatever the kind.** This names the fields it writes and asks a credential for
 * nothing else: a hash of a password, a TOTP secret and a public key of a passkey are the three things a
 * serializer would have put in a file a person downloads and sends on. A kind that gains a field gains
 * nothing here, which is what the issue asks for when it says "credentials without their secrets".
 *
 * What a kind declares it can do, it says by an interface: a credential that can be revoked carries the
 * day it was, and one a party presents by an identifier of its own carries that identifier, which the
 * browser holds already.
 *
 * **That identifier is raw octets** (a credential ID as a WebAuthn authenticator issued it), so it is
 * written in the alphabet the column holding it uses, base64url: a document a person downloads is JSON,
 * and bytes that are not text are what would make it no document at all.
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
