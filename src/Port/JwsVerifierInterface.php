<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use Heidi\Core\Domain\Key\VerifiedJws;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Exception\InvalidJwsException;

interface JwsVerifierInterface
{
    /**
     * Verifies a compact JWS the tenant issued, against its published keys and its algorithms only.
     *
     * @throws InvalidJwsException when the token is malformed, uses another algorithm, names an unknown key or
     *                             does not verify
     */
    public function verify(Tenant $tenant, string $compactJws): VerifiedJws;
}
