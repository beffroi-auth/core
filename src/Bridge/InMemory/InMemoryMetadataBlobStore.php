<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\InMemory;

use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Port\MetadataBlobStoreInterface;
use function ksort;
use function preg_match;
use function sprintf;

final class InMemoryMetadataBlobStore implements MetadataBlobStoreInterface
{
    /**
     * @var array<string, string>
     */
    private array $payloads = [];

    public function write(string $name, string $payload): void
    {
        if (preg_match('/^[A-Za-z0-9._-]+$/D', $name) !== 1) {
            throw new InvalidArgumentException(sprintf('"%s" cannot name a metadata document.', $name));
        }
        $this->payloads[$name] = $payload;
        ksort($this->payloads);
    }

    public function readAll(): array
    {
        return $this->payloads;
    }
}
