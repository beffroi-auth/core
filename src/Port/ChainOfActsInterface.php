<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Journal\Link;
use Beffroi\Core\Domain\Journal\LinkedAct;
use Beffroi\Core\Domain\Tenant\Tenant;

interface ChainOfActsInterface
{
    /**
     * No act is appended to the chain of the tenant until the atomic writes this is called in end.
     */
    public function lockedHead(Tenant $tenant): Link;

    public function head(Tenant $tenant): Link;

    /**
     * @return list<LinkedAct> by position, from the first one past that position
     */
    public function linkedAfter(Tenant $tenant, int $position, int $howMany): array;

    /**
     * The acts of the tenant written with no link, or linked at or before that position.
     */
    public function actsOutsideTheChain(Tenant $tenant, int $keptAfter): int;
}
