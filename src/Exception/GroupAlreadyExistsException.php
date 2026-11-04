<?php

declare(strict_types=1);

namespace Beffroi\Core\Exception;

use RuntimeException;
use function sprintf;
use Throwable;

final class GroupAlreadyExistsException extends RuntimeException implements ExceptionInterface
{
    public static function named(string $name, ?Throwable $previous = null): self
    {
        return new self(sprintf('A group named "%s" already exists in this tenant.', $name), 0, $previous);
    }
}
