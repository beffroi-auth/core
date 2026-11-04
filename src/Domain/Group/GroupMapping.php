<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Group;

use Beffroi\Core\Exception\InvalidArgumentException;
use function array_values;
use function is_string;
use function sprintf;

final readonly class GroupMapping
{
    /**
     * @param array<array-key, Group> $groups by the value that names each
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
     * Only a string is looked up: PHP keys "1" by the integer 1, which true, 1 and 1.0 would reach as an offset.
     *
     * @param list<mixed> $values
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
