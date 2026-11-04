<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Algorithm;

interface NamedAlgorithmInterface
{
    /**
     * @return non-empty-string
     */
    public function name(): string;
}
