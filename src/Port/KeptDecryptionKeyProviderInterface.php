<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Key\KeptEncryptionAlgorithmInterface;
use Beffroi\Core\Domain\Key\UnsealedKey;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\KeyNotFoundException;
use Beffroi\Core\Exception\KmsUnavailableException;

/**
 * Only the keys the tenant keeps, and only those of the algorithm named, whatever "kid" the caller names.
 */
interface KeptDecryptionKeyProviderInterface
{
    /**
     * A key held open and not named is forgotten, so that a key retired since does not stay in the process.
     *
     * @param list<string> $kids
     *
     * @throws KeyNotFoundException
     * @throws KmsUnavailableException
     *
     * @return list<UnsealedKey> in the order named
     */
    public function keptDecryptionKeys(Tenant $tenant, KeptEncryptionAlgorithmInterface $algorithm, array $kids): array;
}
