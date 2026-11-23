<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Chain\ChainInterface;
use Beffroi\Core\Domain\Chain\Link;
use Beffroi\Core\Domain\Chain\LinkedRow;
use Beffroi\Core\Domain\Tenant\Tenant;

interface ChainOfRowsInterface
{
    /**
     * No row is appended to the chain until the atomic writes this is called in end.
     */
    public function lockedHead(Tenant $tenant, ChainInterface $chain): Link;

    public function head(Tenant $tenant, ChainInterface $chain): Link;

    /**
     * @return list<LinkedRow> by position, from the first one past that position
     */
    public function linkedAfter(Tenant $tenant, ChainInterface $chain, int $position, int $howMany): array;

    /**
     * The rows of the record written with no link, or linked at or before that position.
     */
    public function rowsOutsideTheChain(Tenant $tenant, ChainInterface $chain, int $keptAfter): int;

    /**
     * Forgets the rows the chain holds up to that position, and their links.
     */
    public function forgetUpTo(Tenant $tenant, ChainInterface $chain, int $position): int;
}
