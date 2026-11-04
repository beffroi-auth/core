<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\PersonalData;

use function sprintf;

final readonly class BlindIndex implements FieldClassificationInterface
{
    public const string REASON = 'a keyed tag of a sealed value, under the index key of its tenant: it says which rows share a value and never what that value is (decision 0011)';

    /**
     * A tag is matched as it was written, so it stays in the clear: no search could match a sealed one.
     */
    public function finding(StoredField $field): ?string
    {
        return $field->sealedUnder === null
            ? null
            : sprintf('%s is a blind index and is sealed: a tag is matched as it was written, so it is written in the clear.', $field->name);
    }
}
