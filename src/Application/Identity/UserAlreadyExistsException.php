<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Identity;

use Beffroi\Core\Exception\ExceptionInterface;
use RuntimeException;
use Throwable;
use function sprintf;

/**
 * The tenant already has a user with that username or that subject.
 */
final class UserAlreadyExistsException extends RuntimeException implements ExceptionInterface
{
    public static function username(string $username): self
    {
        return new self(sprintf('A user named "%s" already exists in this tenant.', $username));
    }

    public static function subject(string $subject): self
    {
        return new self(sprintf('A user with the subject "%s" already exists in this tenant.', $subject));
    }

    /**
     * The store refused the user at its unique constraints, another request having written its name or its subject
     * between the checks of a use case and its write; it names neither, which a log would keep.
     */
    public static function writtenMeanwhile(Throwable $previous): self
    {
        return new self('A user of this name or of this subject was written meanwhile in this tenant.', 0, $previous);
    }
}
