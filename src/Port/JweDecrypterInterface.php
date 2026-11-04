<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\InvalidJweException;
use Beffroi\Core\Exception\KmsUnavailableException;

interface JweDecrypterInterface
{
    /**
     * RFC 7516 §7.1
     *
     * @throws InvalidJweException
     * @throws KmsUnavailableException
     */
    public function decrypt(Tenant $tenant, string $compactJwe): string;
}
