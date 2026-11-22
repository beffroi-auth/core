<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Confirmation\AddressConfirmation;
use Beffroi\Core\Domain\Identity\User;
use Symfony\Component\Uid\Uuid;

/**
 * The confirmations of one kind of address, each kind in a store of its own (decision 0026).
 */
interface AddressConfirmationRepositoryInterface
{
    public function find(Uuid $id): ?AddressConfirmation;

    /**
     * Expired ones included, oldest first.
     *
     * @return list<AddressConfirmation>
     */
    public function unspentOf(User $user): array;

    public function save(AddressConfirmation $confirmation): void;

    /**
     * A confirmation already gone is forgotten again without complaint.
     */
    public function forget(AddressConfirmation $confirmation): void;
}
