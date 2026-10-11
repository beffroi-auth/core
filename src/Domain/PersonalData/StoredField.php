<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\PersonalData;

/**
 * What the store says of one field a classified model declares, in the terms a line of that declaration
 * answers in: the name a finding gives the field, the family the table holding it seals under, and the
 * family what is stored is sealed under, which is null when it is stored in the clear.
 *
 * A family is the scope a data key is minted under, one key per tenant and per family (decision 0018),
 * and a field seals under the family of its own table. The two families are therefore what makes a field
 * sealed into the scope of another table a finding, which no mapping shows (`Sealed`).
 */
final readonly class StoredField
{
    public function __construct(
        public string $name,
        public string $family,
        public ?string $sealedUnder,
    ) {
    }
}
