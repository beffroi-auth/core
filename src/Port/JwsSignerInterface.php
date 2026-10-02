<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Key\SignatureAlgorithmInterface;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\KeyNotFoundException;
use Beffroi\Core\Exception\KmsUnavailableException;

interface JwsSignerInterface
{
    /**
     * The compact serialization (RFC 7515 §7.1); "alg" and "kid" are set here, the rest by the caller.
     *
     * @param array<string, mixed> $protectedHeader
     *
     * @throws KeyNotFoundException when the tenant has no active key for the algorithm
     * @throws KmsUnavailableException when the KMS does not answer to open the key that signs
     */
    public function sign(Tenant $tenant, SignatureAlgorithmInterface $algorithm, string $payload, array $protectedHeader = []): string;
}
