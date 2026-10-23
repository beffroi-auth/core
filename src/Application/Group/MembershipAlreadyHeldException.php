<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Group;

use Beffroi\Core\Exception\ExceptionInterface;
use RuntimeException;
use Throwable;

/**
 * The person is already a member of that group: the store refused the membership at its unique constraint, another
 * request having written it between the reading of the memberships and this write (beffroi/saml S-56). It names
 * neither the person nor the group, which a log would keep.
 */
final class MembershipAlreadyHeldException extends RuntimeException implements ExceptionInterface
{
    public static function writtenMeanwhile(Throwable $previous): self
    {
        return new self('The membership was written meanwhile.', 0, $previous);
    }
}
