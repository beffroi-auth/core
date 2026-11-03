<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\InMemory;

use Beffroi\Core\Port\AuthenticatorListStoreInterface;

final class InMemoryAuthenticatorListStore implements AuthenticatorListStoreInterface
{
    private ?string $document = null;

    public function write(string $document): void
    {
        $this->document = $document;
    }

    public function read(): ?string
    {
        return $this->document;
    }
}
