<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\PersonalData;

/**
 * A class answers for every field it stores, its parent's included through ...parent::classifiedFields().
 */
interface ClassifiedFieldsInterface
{
    /**
     * @return array<string, FieldClassificationInterface> by the name of the stored property
     */
    public static function classifiedFields(): array;
}
