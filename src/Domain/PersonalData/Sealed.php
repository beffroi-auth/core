<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\PersonalData;

use function sprintf;

/**
 * A personal datum, sealed under the family of its own table: sealed under another family, it would escape
 * the destruction of that table's keys.
 */
final readonly class Sealed implements FieldClassificationInterface
{
    public function __construct(
        public string $family,
    ) {
    }

    public function finding(StoredField $field): ?string
    {
        if ($field->sealedUnder === null) {
            return sprintf(
                '%s is declared sealed under the "%s" family and what stores it does not seal it.',
                $field->name,
                $this->family,
            );
        }

        if ($this->family !== $field->family) {
            return sprintf(
                '%s is declared sealed under the "%s" family, and a field seals under its own table, so under "%s".',
                $field->name,
                $this->family,
                $field->family,
            );
        }

        if ($field->sealedUnder !== $this->family) {
            return sprintf(
                '%s is declared sealed under the "%s" family and is sealed under "%s", which is another family: a field of an embeddable several entities embed cannot carry the type of one of their tables.',
                $field->name,
                $this->family,
                $field->sealedUnder,
            );
        }

        return null;
    }
}
