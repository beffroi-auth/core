<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Group;

use Beffroi\Core\Exception\InvalidArgumentException;
use function array_values;
use function is_string;
use function sprintf;

/**
 * What the declaration of an upstream provider makes of the values of the attribute it names for groups
 * (decision 0002): each value it names is a group the tenant already holds, compared byte for byte, and a
 * value it does not name is nothing. A group is never created from what a provider sends: the values a
 * provider may assert are the deployment's to list, and the groups they lead to are the tenant's.
 *
 * What a provider asserts is read as it came, and only a string is a value: PHP keys the declared value "1"
 * by the integer 1, which true, 1 and 1.0 would reach as an offset, so whatever is not a string is ignored
 * before any lookup.
 */
final readonly class GroupMapping
{
    /**
     * @param array<array-key, Group> $groups the group of the tenant each value names, by that value, which PHP keys
     *                                       by its integer when it is one written in digits
     *
     * @throws InvalidArgumentException when a value is empty
     */
    public function __construct(
        private array $groups,
    ) {
        foreach ($groups as $value => $group) {
            if ($value === '') {
                throw new InvalidArgumentException(sprintf('A value an upstream provider asserts for the group "%s" must not be empty.', $group->name));
            }
        }
    }

    /**
     * The groups these values name, each once, in the order they are first named; a value the mapping does
     * not name is ignored.
     *
     * @param list<mixed> $values the values of the attribute, as the provider sent them
     *
     * @return list<Group>
     */
    public function groupsOf(array $values): array
    {
        $named = [];
        foreach ($values as $value) {
            if (! is_string($value)) {
                continue;
            }
            $group = $this->groups[$value] ?? null;
            if ($group !== null) {
                $named[$group->id->toRfc4122()] = $group;
            }
        }

        return array_values($named);
    }
}
