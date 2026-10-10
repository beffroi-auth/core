<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\InMemory;

use function array_values;
use function array_filter;
use Beffroi\Core\Domain\Listing\Period;
use function hash_equals;
use Beffroi\Core\Domain\Credential\Credential;
use Beffroi\Core\Domain\Credential\ExternallyIdentifiedCredentialInterface;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Domain\Listing\Listing;
use Beffroi\Core\Domain\Listing\Pagination;
use Beffroi\Core\Port\CanPageCredentialsInterface;
use Beffroi\Core\Port\CredentialRepositoryInterface;
use Symfony\Component\Uid\Uuid;
use function usort;

final class InMemoryCredentialRepository implements CanPageCredentialsInterface, CredentialRepositoryInterface
{
    /**
     * @var array<string, Credential>
     */
    private array $credentials = [];

    /**
     * @param class-string<Credential> $kind
     *
     * @return Listing<Credential>
     */
    public function pageOfCredentials(User $user, string $kind, Pagination $pagination, ?Period $period = null): Listing
    {
        return Listing::of(
            array_values(array_filter(
                $this->findByUser($user, $kind),
                static fn (Credential $credential): bool => $period?->holds($credential->createdAt) !== false,
            )),
            $pagination,
        );
    }

    public function findByUser(User $user, string $kind = Credential::class): array
    {
        $found = [];
        foreach ($this->credentials as $credential) {
            if ($credential instanceof $kind && $credential->user->id->equals($user->id)) {
                $found[] = $credential;
            }
        }
        usort($found, static fn (Credential $a, Credential $b): int => $a->createdAt <=> $b->createdAt);

        return $found;
    }

    public function find(Uuid $id): ?Credential
    {
        return $this->credentials[$id->toRfc4122()] ?? null;
    }

    public function findByExternalId(Tenant $tenant, string $kind, string $externalId): ?Credential
    {
        foreach ($this->credentials as $credential) {
            if (
                $credential instanceof $kind
                && $credential instanceof ExternallyIdentifiedCredentialInterface
                && $credential->tenant->equals($tenant)
                && hash_equals($credential->externalId, $externalId)
            ) {
                return $credential;
            }
        }

        return null;
    }

    public function save(Credential $credential): void
    {
        $this->credentials[$credential->id->toRfc4122()] = $credential;
    }
}
