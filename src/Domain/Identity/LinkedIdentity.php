<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Identity;

use DateTimeImmutable;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Exception\InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

/**
 * The identity a user has at an upstream provider, recognised by (provider, subject); the claims are the
 * last snapshot received.
 */
final class LinkedIdentity
{
    /**
     * Restated from the user (decision 0002).
     */
    public readonly Tenant $tenant;

    /**
     * The blind indexes of the provider and of the subject, which is what the unique constraint of this
     * table is held on and what a federated login will match an identity by: both columns are sealed, and a
     * sealed column holds a different ciphertext for every write of the same value, so neither a constraint
     * nor a lookup on them would do anything at all (decision 0011).
     *
     * They sit on an entity that knows nothing of a database for the reason the two of `User` do: a column
     * is written by the mapping of a property, and a tag has to be written in the same statement as the
     * value it indexes. Nothing of the domain derives them, and nothing can.
     */
    public private(set) string $providerIndex = '';

    public private(set) string $subjectIndex = '';

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
