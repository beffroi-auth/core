<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\PersonalData;

final readonly class StoredField
{
    public function __construct(
        public string $name,
        public string $family,
        public ?string $sealedUnder,
    ) {
    }
}
