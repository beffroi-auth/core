<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Chain;

/**
 * A record the provider keeps as a chain per tenant, whose head it signs (decision 0060).
 */
interface ChainInterface
{
    /**
     * @return non-empty-string lowercase letters and underscores, stable: the rows, the files and the signatures name it
     */
    public function name(): string;
}
