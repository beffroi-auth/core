<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Chain\ChainInterface;
use Beffroi\Core\Domain\Chain\RelinkedChain;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\RuntimeException;

interface RelinkableChainsInterface
{
    /**
     * Every row of the record the database holds linked again, in the order of the chain and the rows it does not
     * hold last, from the link the first linked row names before it.
     *
     * @throws RuntimeException when the store may not rewrite the links of the chain
     */
    public function relink(Tenant $tenant, ChainInterface $chain): RelinkedChain;
}
