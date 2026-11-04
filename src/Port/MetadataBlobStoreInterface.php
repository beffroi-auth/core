<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Exception\InvalidArgumentException;

/**
 * What is written here was verified first; the ceremonies read it and never write it.
 */
interface MetadataBlobStoreInterface
{
    /**
     * @param non-empty-string $name letters, digits, dots, dashes and underscores
     *
     * @throws InvalidArgumentException
     */
    public function write(string $name, string $payload): void;

    /**
     * @return array<string, string> the payloads by name
     */
    public function readAll(): array;
}
