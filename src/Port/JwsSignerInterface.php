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
     * RFC 7515 §7.1
     *
     * @param array<string, mixed> $protectedHeader the members besides "alg" and "kid", which are set here
     *
     * @throws KeyNotFoundException
     * @throws KmsUnavailableException
     */
    public function sign(Tenant $tenant, SignatureAlgorithmInterface $algorithm, string $payload, array $protectedHeader = []): string;
}
