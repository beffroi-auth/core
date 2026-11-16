<?php

declare(strict_types=1);

namespace Beffroi\Core\Exception;

use RuntimeException;
use function sprintf;

/**
 * A membership the tenant cannot give or take back itself: the person already is a member, is not one, or holds it by a
 * directory, which alone writes what it holds (decision 0002).
 */
final class GroupMembershipException extends RuntimeException implements ExceptionInterface
{
    public static function alreadyAMember(string $group): self
    {
        return new self(sprintf('This person is already a member of the group "%s".', $group));
    }

    public static function notAMember(string $group): self
    {
        return new self(sprintf('This person is not a member of the group "%s".', $group));
    }

    /**
     * A group a directory fills, by SCIM, takes its members from that directory alone.
     */
    public static function filledByADirectory(string $group): self
    {
        return new self(sprintf('The members of the group "%s" are written by the directory that fills it, never by the tenant.', $group));
    }

    /**
     * A membership a directory gave is taken back by that directory: the next assertion or the next push would write it
     * again.
     */
    public static function givenByADirectory(string $group): self
    {
        return new self(sprintf('This person is a member of the group "%s" by a directory, which alone takes the membership back.', $group));
    }
}
