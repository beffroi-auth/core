<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use Heidi\Core\Domain\Key\SignatureAlgorithm;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Exception\KeyNotFoundException;
use Heidi\Core\Exception\KmsUnavailableException;

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
    public function sign(Tenant $tenant, SignatureAlgorithm $algorithm, string $payload, array $protectedHeader = []): string;
}
