<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Identity;

use Beffroi\Core\Exception\ExceptionInterface;
use RuntimeException;
use Throwable;
use function sprintf;

/**
 * Another user of the tenant is already that identity at that provider. A row (provider, subject) names one
 * user and one only (decision 0002): whoever presents that subject is that user, so moving it would hand one
 * account the sign-ins of another.
 */
final class IdentityAlreadyLinkedException extends RuntimeException implements ExceptionInterface
{
    public static function toAnotherUser(string $provider, string $subject, string $username): self
    {
        return new self(sprintf('The subject "%s" of the provider "%s" is already the user "%s" in this tenant.', $subject, $provider, $username));
    }

    /**
     * The store refused the identity at its unique constraint, another request having linked it between the lookup
     * and the write; it names neither the subject nor the person, which a log would keep.
     */
    public static function linkedMeanwhile(Throwable $previous): self
    {
        return new self('The identity was linked meanwhile in this tenant.', 0, $previous);
    }
}
