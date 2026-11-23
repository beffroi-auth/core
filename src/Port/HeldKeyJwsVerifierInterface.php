<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Key\VerifiedJws;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\InvalidJwsException;

/**
 * Against every signing key the tenant holds, retired ones included: for a document the provider signed for itself and
 * keeps, never for one a third party presents.
 */
interface HeldKeyJwsVerifierInterface
{
    /**
     * @throws InvalidJwsException
     */
    public function verify(Tenant $tenant, string $compactJws): VerifiedJws;
}
