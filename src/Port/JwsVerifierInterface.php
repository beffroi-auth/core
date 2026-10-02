<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Key\VerifiedJws;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\InvalidJwsException;

interface JwsVerifierInterface
{
    /**
     * Against the published keys and the algorithms of the tenant only.
     *
     * @throws InvalidJwsException
     */
    public function verify(Tenant $tenant, string $compactJws): VerifiedJws;
}
