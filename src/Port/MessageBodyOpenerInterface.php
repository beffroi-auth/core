<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\InvalidJweException;
use Beffroi\Core\Exception\KmsUnavailableException;

interface MessageBodyOpenerInterface
{
    /**
     * @throws InvalidJweException
     * @throws KmsUnavailableException
     */
    public function open(Tenant $tenant, string $sealed): string;
}
