<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Credential\Credential;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Tenant\Tenant;
use Symfony\Component\Uid\Uuid;

/**
 * The credentials of a user, whatever their kind: a kind is named by its class, never by a method of
 * its own, so that a new kind adds a table (architecture §5.3) and nothing here.
 */
interface CredentialRepositoryInterface
{
    /**
     * The credentials of the user, of the kind asked for or of every kind, the revoked ones included,
     * oldest first.
     *
     * @template T of Credential
     *
     * @param class-string<T> $kind
     *
     * @return list<T>
     */
    public function findByUser(User $user, string $kind = Credential::class): array;

    /**
     * By the technical identifier, whatever the kind.
     */
    public function find(Uuid $id): ?Credential;

    /**
     * By the identifier a party presents for a credential of that kind, within the tenant: the credential
     * ID of WebAuthn (Level 3 §6.3.2) for a passkey; a password has none. A revoked credential is found,
     * the caller tells it apart.
     *
     * @template T of Credential
     *
     * @param class-string<T> $kind
     *
     * @return T|null
     */
    public function findByExternalId(Tenant $tenant, string $kind, string $externalId): ?Credential;

    public function save(Credential $credential): void;
}
