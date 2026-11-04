<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\PersonalData;

interface FieldClassificationInterface
{
    public function finding(StoredField $field): ?string;
}
