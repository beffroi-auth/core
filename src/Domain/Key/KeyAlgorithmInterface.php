<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Key;

use Beffroi\Core\Domain\Algorithm\NamedAlgorithmInterface;

interface KeyAlgorithmInterface extends NamedAlgorithmInterface
{
    /**
     * RFC 7518 §6.1
     *
     * @return non-empty-string
     */
    public function keyType(): string;

    public function keyUse(): KeyUse;
}
