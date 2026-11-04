<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Group;

use Beffroi\Core\Exception\ExceptionInterface;
use RuntimeException;
use Throwable;

/**
 * Names neither the person nor the group, which a log would keep.
 */
final class MembershipAlreadyHeldException extends RuntimeException implements ExceptionInterface
{
    public static function writtenMeanwhile(Throwable $previous): self
    {
        return new self('The membership was written meanwhile.', 0, $previous);
    }
}
