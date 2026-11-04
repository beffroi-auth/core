<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Credential\Credential;
use Beffroi\Core\Domain\Tenant\Tenant;

/**
 * It removes, where a revocation marks: a credential the floor of a tenant forbids is one this provider must
 * not hold, secret included.
 */
interface CanForgetCredentialsInterface
{
    /**
     * @return iterable<Credential>
     */
    public function credentialsOf(Tenant $tenant): iterable;

    /**
     * A credential already gone is forgotten again without complaint: an interrupted sweep runs again.
     */
    public function forget(Credential $credential): void;
}
