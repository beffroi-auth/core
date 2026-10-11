<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\PersonalData;

/**
 * A class of the model that says, field by field, what it holds of a person: a field is a personal datum
 * sealed under the data key of a family (`Sealed`), a keyed tag of a sealed value (`BlindIndex`), a field
 * a dump gives away with the reason that gives nothing away (`NoPersonalDatum`), or a personal datum owed
 * to the issue that seals it (`Owed`). Decisions 0011 and 0018.
 *
 * **The classification is the model's own**, in the package that owns the entity, so that what this
 * provider holds of a person is answered by the class that holds it: a use case, a command or an export
 * asks the class, and a second place that writes these rows reads the same answer. What a field seals
 * under is still the type its mapping gives it, which is the bundle's (`EncryptedColumnTypes`), and what
 * holds the two together is the walk of the schema: it gives every line here the column the mapping gives
 * its field, refuses a column no line names and a line no column carries, and refuses a field sealed into
 * the scope of a table that is not its own.
 *
 * A line is named once, on the class whose mapping declares the field, and **a class answers for every
 * field it stores**: it merges the lines of its parent as it inherits its mapping, with
 * `...parent::classifiedFields()`, so that one answer is the whole of what one row holds of a person. A
 * value object several fields of a table come from answers for its own fields, and the class that holds
 * it answers for the rest.
 */
interface ClassifiedFieldsInterface
{
    /**
     * One line per stored field, by the name of the property the store holds.
     *
     * @return array<string, FieldClassificationInterface>
     */
    public static function classifiedFields(): array;
}
