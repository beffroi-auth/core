<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Tenant\Tenant;
use RuntimeException;

/**
 * The tenant of the unit of work in progress, the one a request or a message is served for.
 *
 * Every use case receives its tenant as a parameter, so this port exists for the few places that are not
 * a use case and have nothing to receive it from: a Doctrine type, which sees no row, and whatever
 * composes what such a type needs from the tenant that is ambient (decision 0018).
 *
 * There is no tenant to guess. An implementation either names the one of the unit of work or throws, and
 * a caller that cannot have one refuses rather than falling back on a default: a value sealed outside the
 * scope of its tenant is a value no destruction of that tenant's keys reaches.
 */
interface AmbientTenantInterface
{
    /**
     * @throws RuntimeException when no tenant is ambient, or when the one that is does not match the
     *                          configuration of the deployment
     */
    public function provide(): Tenant;
}
