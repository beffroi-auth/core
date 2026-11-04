<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\InMemory;

use Beffroi\Core\Domain\Identity\LinkedIdentity;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Port\LinkedIdentitiesOfAPersonInterface;
use Beffroi\Core\Port\LinkedIdentityRepositoryInterface;
use function usort;

final class InMemoryLinkedIdentityRepository implements LinkedIdentitiesOfAPersonInterface, LinkedIdentityRepositoryInterface
{
    /**
     * @var array<string, LinkedIdentity>
     */
    private array $identities = [];

    public function find(Tenant $tenant, string $provider, string $subject): ?LinkedIdentity
    {
        foreach ($this->identities as $identity) {
            if ($identity->tenant->equals($tenant) && $identity->provider === $provider && $identity->subject === $subject) {
                return $identity;
            }
        }

        return null;
    }

    public function of(User $user): array
    {
        $theirs = [];
        foreach ($this->identities as $identity) {
            if ($identity->user->id->equals($user->id)) {
                $theirs[] = $identity;
            }
        }
        usort($theirs, static fn (LinkedIdentity $a, LinkedIdentity $b): int => $b->linkedAt <=> $a->linkedAt);

        return $theirs;
    }

    public function save(LinkedIdentity $identity): void
    {
        $this->identities[$identity->id->toRfc4122()] = $identity;
    }
}
