<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\PersonalData;

/**
 * What one line of a classified model says of one field, and what it has to say about the column the
 * store holds that field in. Decision 0011 asks for the test this answers: "un test énumère les colonnes
 * du schéma et refuse une colonne qui n'est ni déclarée hors données personnelles, ni d'un type chiffré";
 * a line is that declaration, and this is the one question the walk of the schema asks it.
 */
interface FieldClassificationInterface
{
    /**
     * What the line and the column the field is stored in disagree on, or null when they agree.
     */
    public function finding(StoredField $field): ?string;
}
