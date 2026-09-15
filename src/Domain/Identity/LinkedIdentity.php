<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Identity;

use DateTimeImmutable;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Exception\InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

/**
 * The identity a user has at an upstream provider: a Google "sub", an Entra "oid", a SAML NameID. The pair
 * (provider, subject) is what the user is recognised by on their next login; the claims are the snapshot
 * received last, kept for provisioning and diagnosis.
 */
final class LinkedIdentity
{
    /**
     * The tenant of the user, restated so that every stored linked identity carries it (decision 0002).
     */
    public readonly Tenant $tenant;

    /**
     * @param array<string, mixed> $claims
     */
    public function __construct(
        public private(set) Uuid $id,
        public readonly User $user,
        public readonly string $provider,
        public readonly string $subject,
        public private(set) array $claims,
        public readonly DateTimeImmutable $linkedAt,
        public private(set) DateTimeImmutable $lastSeenAt,
    ) {
        if ($provider === '') {
            throw new InvalidArgumentException('A linked identity names its provider.');
        }
        if ($subject === '') {
            throw new InvalidArgumentException('A linked identity carries the subject the provider gave.');
        }
        $this->tenant = $user->tenant;
    }

    /**
     * @param array<string, mixed> $claims
     */
    public function refresh(array $claims, DateTimeImmutable $now): void
    {
        $this->claims = $claims;
        $this->lastSeenAt = $now;
    }
}
