<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\InvalidJweException;
use Beffroi\Core\Exception\KmsUnavailableException;

/**
 * Opens what a transport carried, sealed by MessageBodySealerInterface (decision 0020). A port of its own,
 * because sealing and opening are two capabilities and one process does one of them: what answers browsers
 * seals and holds no key material, what consumes a queue opens.
 */
interface MessageBodyOpenerInterface
{
    /**
     * @param string $sealed the compact JWE a transport carried
     *
     * @throws InvalidJweException when the body is not a JWE this provider seals, when it names no key or
     *                             a key the tenant does not seal to, when it was sealed under another
     *                             scope than the tenant asked for, or when it does not open
     * @throws KmsUnavailableException when the KMS does not answer to open the key the body names
     */
    public function open(Tenant $tenant, string $sealed): string;
}
