<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use Beffroi\Core\Exception\InvalidArgumentException;

interface NameableCredentialInterface
{
    public string $name { get; }

    /**
     * @throws InvalidArgumentException when the name is empty or too long
     */
    public function rename(string $name): void;
}
