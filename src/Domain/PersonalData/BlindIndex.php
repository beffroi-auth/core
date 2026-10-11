<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\PersonalData;

use function sprintf;

/**
 * A field that holds a keyed tag of a sealed value and never the value itself: the blind index of
 * decision 0011, a HMAC of the value under the index key of its tenant, which the store writes in the
 * same statement as the value it indexes and which a search matches on where that value is sealed. It is
 * what a unique constraint holds, a sealed column holding a different ciphertext for every write of the
 * same value.
 *
 * It is a field of the model and no datum of the person: a tag says which rows share a value and never
 * what that value is, which is the one thing a deployment gives up by sealing a column it searches.
 */
final readonly class BlindIndex implements FieldClassificationInterface
{
    public const string REASON = 'a keyed tag of a sealed value, under the index key of its tenant: it says which rows share a value and never what that value is (decision 0011)';

    /**
     * A tag is matched as it was written, so it is written in the clear: one that seals would be a tag no
     * search could match, and the column it indexes would have lost the only way it is looked up by.
     */
    public function finding(StoredField $field): ?string
    {
        return $field->sealedUnder === null
            ? null
            : sprintf('%s is a blind index and is sealed: a tag is matched as it was written, so it is written in the clear.', $field->name);
    }
}
