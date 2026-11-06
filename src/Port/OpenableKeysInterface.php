<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\KmsUnavailableException;
use Beffroi\Core\Exception\RuntimeException;

/**
 * Keys a process opens before a request asks for them, through the memory that request would fill.
 */
interface OpenableKeysInterface
{
    /**
     * @throws KmsUnavailableException
     * @throws RuntimeException when an envelope or a data key is refused
     */
    public function open(Tenant $tenant): void;
}
