<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

interface ExternallyIdentifiedCredentialInterface
{
    public string $externalId { get; }
}
