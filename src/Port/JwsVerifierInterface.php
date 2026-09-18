<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use Heidi\Core\Domain\Key\VerifiedJws;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Exception\InvalidJwsException;

interface JwsVerifierInterface
{
    /**
     * Against the published keys and the algorithms of the tenant only.
     *
     * @throws InvalidJwsException
     */
    public function verify(Tenant $tenant, string $compactJws): VerifiedJws;
}
