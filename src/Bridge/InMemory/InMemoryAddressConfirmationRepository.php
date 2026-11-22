<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\InMemory;

use Beffroi\Core\Domain\Confirmation\AddressConfirmation;
use Beffroi\Core\Domain\Identity\User;
use Beffroi\Core\Port\AddressConfirmationRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class InMemoryAddressConfirmationRepository implements AddressConfirmationRepositoryInterface
{
    /**
     * @var array<string, AddressConfirmation>
     */
    private array $confirmations = [];

    public function find(Uuid $id): ?AddressConfirmation
    {
        return $this->confirmations[$id->toRfc4122()] ?? null;
    }

    public function unspentOf(User $user): array
    {
        $found = [];
        foreach ($this->confirmations as $confirmation) {
            if (! $confirmation->isSpent() && $confirmation->isFor($user)) {
                $found[] = $confirmation;
            }
        }

        return $found;
    }

    public function save(AddressConfirmation $confirmation): void
    {
        $this->confirmations[$confirmation->id->toRfc4122()] = $confirmation;
    }

    public function forget(AddressConfirmation $confirmation): void
    {
        unset($this->confirmations[$confirmation->id->toRfc4122()]);
    }
}
