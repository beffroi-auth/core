<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use Beffroi\Core\Domain\Session\AuthenticationMethod;

interface ProvingCredentialInterface
{
    /**
     * Empty for a revoked credential.
     *
     * @return list<AuthenticationMethod>
     */
    public function provableMethods(): array;
}
