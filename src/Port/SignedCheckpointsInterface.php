<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\RuntimeException;

/**
 * Kept where the role of the database cannot write, so that whoever holds the database alone can neither remove the
 * last signature nor put an older one back.
 */
interface SignedCheckpointsInterface
{
    /**
     * @return non-empty-string|null the compact JWS kept last
     */
    public function last(Tenant $tenant): ?string;

    /**
     * @throws RuntimeException
     */
    public function keep(Tenant $tenant, string $compactJws): void;
}
