<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\InMemory;

use Beffroi\Core\Domain\Identity\LinkedIdentity;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Port\LinkedIdentityRepositoryInterface;

/**
 * For tests and for the examples: nothing here survives the process.
 */
final class InMemoryLinkedIdentityRepository implements LinkedIdentityRepositoryInterface
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

    public function save(LinkedIdentity $identity): void
    {
        $this->identities[$identity->id->toRfc4122()] = $identity;
    }
}
