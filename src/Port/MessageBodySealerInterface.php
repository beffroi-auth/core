<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\KeyNotFoundException;
use Beffroi\Core\Exception\RuntimeException;

/**
 * The tenant is written in the authenticated data of the seal: a body opens under no other scope.
 */
interface MessageBodySealerInterface
{
    /**
     * @return string a compact JWE
     *
     * @throws KeyNotFoundException when the tenant has no active message key, rather than writing in the clear
     * @throws RuntimeException
     */
    public function seal(Tenant $tenant, string $body): string;
}
