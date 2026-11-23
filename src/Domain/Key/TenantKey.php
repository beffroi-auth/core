<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Key;

use DateInterval;
use DateTimeImmutable;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Exception\LogicException;
use function is_string;
use SensitiveParameter;
use function sprintf;

final class TenantKey
{
    public private(set) KeyStatus $status = KeyStatus::Staged;

    public private(set) ?DateTimeImmutable $activatedAt = null;

    public private(set) ?DateTimeImmutable $retiringAt = null;

    public private(set) ?DateTimeImmutable $retirementDueAt = null;

    public private(set) ?DateTimeImmutable $retiredAt = null;

    /**
     * @param array<string, string> $publicJwk
     */
    public function __construct(
        public readonly string $kid,
        public readonly Tenant $tenant,
        public readonly KeyAlgorithmInterface $algorithm,
        public readonly array $publicJwk,
        #[SensitiveParameter]
        public readonly string $sealedPrivateJwk,
        public readonly DateTimeImmutable $createdAt,
        public readonly DateTimeImmutable $activationDueAt,
    ) {
        if ($activationDueAt < $createdAt) {
            throw new InvalidArgumentException('The activation of a key is due when it is created at the earliest.');
        }
        if ($kid === '') {
            throw new InvalidArgumentException('A key has a key identifier.');
        }
        if ($sealedPrivateJwk === '') {
            throw new InvalidArgumentException('A key carries its sealed private material.');
        }
        foreach ([
            'kid' => $kid,
            'kty' => $algorithm->keyType(),
            'alg' => $algorithm->name(),
            'use' => $algorithm->keyUse()->value,
        ] as $member => $expected) {
            $actual = $publicJwk[$member] ?? null;
            if (! is_string($actual) || $actual !== $expected) {
                throw new InvalidArgumentException(sprintf('The public JWK must carry "%s" = "%s".', $member, $expected));
            }
        }
        foreach ($publicJwk as $member => $value) {
            if (! is_string($member) || ! is_string($value)) {
                throw new InvalidArgumentException('The public JWK is a map of string members.');
            }
        }
        foreach (['d', 'p', 'q', 'dp', 'dq', 'qi', 'k', 'oth'] as $private) {
            if (isset($publicJwk[$private])) {
                throw new InvalidArgumentException(sprintf('The public JWK must not carry the private member "%s".', $private));
            }
        }
    }

    public function activate(DateTimeImmutable $now): void
    {
        if ($this->status !== KeyStatus::Staged) {
            throw new LogicException(sprintf('Only a staged key can be activated; this one is %s.', $this->status->value));
        }
        $this->status = KeyStatus::Active;
        $this->activatedAt = $now;
    }

    /**
     * A staged key starts retiring without ever signing when a newer key took over first, or a compromise ends it.
     */
    public function startRetiring(DateTimeImmutable $now, DateTimeImmutable $retirementDueAt): void
    {
        if ($this->status !== KeyStatus::Active && $this->status !== KeyStatus::Staged) {
            throw new LogicException(sprintf('Only an active or a staged key can start retiring; this one is %s.', $this->status->value));
        }
        if ($retirementDueAt < $now) {
            throw new InvalidArgumentException('The retirement of a key is due when it starts retiring at the earliest.');
        }
        $this->status = KeyStatus::Retiring;
        $this->retiringAt = $now;
        $this->retirementDueAt = $retirementDueAt;
    }

    public function retire(DateTimeImmutable $now): void
    {
        if ($this->status !== KeyStatus::Retiring) {
            throw new LogicException(sprintf('Only a retiring key can be retired; this one is %s.', $this->status->value));
        }
        $this->status = KeyStatus::Retired;
        $this->retiredAt = $now;
    }

    public function isStaged(): bool
    {
        return $this->status === KeyStatus::Staged;
    }

    public function isActive(): bool
    {
        return $this->status === KeyStatus::Active;
    }

    public function isInService(): bool
    {
        return $this->status->isInService();
    }

    public function isPublished(): bool
    {
        return $this->status->isInService() && $this->algorithm instanceof PublishedKeyAlgorithmInterface;
    }

    public function isDueForActivation(DateTimeImmutable $now): bool
    {
        return $this->status === KeyStatus::Staged && $this->activationDueAt <= $now;
    }

    public function wasCreatedBefore(self $other): bool
    {
        return $this->createdAt < $other->createdAt;
    }

    public function isDue(DateTimeImmutable $now): bool
    {
        return $this->status === KeyStatus::Retiring && $this->retirementDueAt !== null && $this->retirementDueAt <= $now;
    }

    /**
     * The age is counted from the activation, not the creation: a key that waits staged is not working.
     */
    public function hasReachedItsAge(DateTimeImmutable $now, DateInterval $age): bool
    {
        return $this->status === KeyStatus::Active && $this->activatedAt !== null && $this->activatedAt->add($age) <= $now;
    }
}
