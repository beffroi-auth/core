<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Group;

use Beffroi\Core\Exception\InvalidArgumentException;
use function array_values;
use function sprintf;

/**
 * What the declaration of an upstream provider makes of the values of the attribute it names for groups
 * (decision 0002): each value it names is a group the tenant already holds, compared byte for byte, and a
 * value it does not name is nothing. A group is never created from what a provider sends: the values a
 * provider may assert are the deployment's to list, and the groups they lead to are the tenant's.
 */
final readonly class GroupMapping
{
    /**
     * @param array<string, Group> $groups the group of the tenant each value names, by that value
     *
     * @throws InvalidArgumentException when a value is empty
     */
    public function __construct(
        private array $groups,
    ) {
        foreach ($groups as $value => $group) {
            if ($value === '') {
                throw new InvalidArgumentException(sprintf('A value an upstream provider asserts for the group "%s" is not empty.', $group->name));
            }
        }
    }

    /**
     * The groups these values name, each once, in the order they are first named; a value the mapping does
     * not name is ignored.
     *
     * @param list<string> $values the values of the attribute, as the provider sent them
     *
     * @return list<Group>
     */
    public function groupsOf(array $values): array
    {
        $named = [];
        foreach ($values as $value) {
            $group = $this->groups[$value] ?? null;
            if ($group !== null) {
                $named[$group->id->toRfc4122()] = $group;
            }
        }

        return array_values($named);
    }
}
