<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Identity;

use Beffroi\Core\Domain\Identity\EmailAddress;
use Beffroi\Core\Exception\ExceptionInterface;
use RuntimeException;
use function sprintf;

/**
 * An upstream provider said of a person what it may not write (decision 0029): an address outside the
 * domains it answers for, or one another person of the tenant holds. Raised before anything is written, so
 * a refusal creates nobody and changes nobody.
 *
 * Neither message carries the address, which is a personal datum and goes wherever an exception is logged;
 * the domain of the first is what the operator who declared the provider needs to read.
 */
final class UpstreamProvisioningRefusedException extends RuntimeException implements ExceptionInterface
{
    public static function outsideItsDomains(string $provider, EmailAddress $email): self
    {
        return new self(sprintf('The upstream provider "%s" does not answer for the domain "%s".', $provider, $email->domain()));
    }

    /**
     * No existing account is linked on its own: linking one is spike 12's, after a local re-authentication
     * (beffroi-auth/beffroi#85).
     */
    public static function heldByAnotherPerson(string $provider): self
    {
        return new self(sprintf(
            'Another person of this tenant holds the address the upstream provider "%s" gave, and no existing account is linked to it on its own.',
            $provider,
        ));
    }
}
