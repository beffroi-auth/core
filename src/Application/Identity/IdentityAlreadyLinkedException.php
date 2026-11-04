<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Identity;

use Beffroi\Core\Exception\ExceptionInterface;
use RuntimeException;
use Throwable;
use function sprintf;

final class IdentityAlreadyLinkedException extends RuntimeException implements ExceptionInterface
{
    public static function toAnotherUser(string $provider, string $subject, string $username): self
    {
        return new self(sprintf('The subject "%s" of the provider "%s" is already the user "%s" in this tenant.', $subject, $provider, $username));
    }

    /**
     * Names neither the subject nor the person, which a log would keep.
     */
    public static function linkedMeanwhile(Throwable $previous): self
    {
        return new self('The identity was linked meanwhile in this tenant.', 0, $previous);
    }
}
