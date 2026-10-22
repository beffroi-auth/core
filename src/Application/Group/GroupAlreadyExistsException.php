<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Group;

use Beffroi\Core\Exception\ExceptionInterface;
use RuntimeException;
use function sprintf;

/**
 * The tenant already has a group with that name.
 */
final class GroupAlreadyExistsException extends RuntimeException implements ExceptionInterface
{
    public static function named(string $name): self
    {
        return new self(sprintf('A group named "%s" already exists in this tenant.', $name));
    }
}
