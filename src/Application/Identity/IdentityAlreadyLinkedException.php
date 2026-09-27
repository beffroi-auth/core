<?php

declare(strict_types=1);

namespace Heidi\Core\Application\Identity;

use Heidi\Core\Exception\ExceptionInterface;
use RuntimeException;
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
}
