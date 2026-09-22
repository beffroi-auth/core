<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Key;

use DateTimeImmutable;
use Heidi\Core\Domain\Tenant\Tenant;
use Heidi\Core\Exception\InvalidArgumentException;
use Heidi\Core\Exception\LogicException;
use function is_string;
use SensitiveParameter;
use function sprintf;

/**
 * A key pair of a tenant: the public JWK in clear, the private JWK sealed by the KMS, the "kid" its
 * RFC 7638 thumbprint. Staged, active, retiring, retired (architecture §3.3); every date is a fact of the
 * key, the two due dates included: when the staging window ends and when the retirement window ends.
 *
 * What the key is for is what its algorithm is for, written once: a key of a signature algorithm signs,
 * a key of a key encryption algorithm opens a JWE, and the "use" its JWK publishes is asserted against
 * the algorithm rather than trusted (RFC 7517 §4.2). The two lives are the same life, so there is one
 * class, one repository and one set of commands for both.
 */
final class TenantKey
{
    public private(set) KeyStatus $status = KeyStatus::Staged;

    public private(set) ?DateTimeImmutable $activatedAt = null;

    public private(set) ?DateTimeImmutable $retiringAt = null;

    /**
     * When the last JWS this key could have signed has expired.
     */
    public private(set) ?DateTimeImmutable $retirementDueAt = null;

    public private(set) ?DateTimeImmutable $retiredAt = null;

    /**
     * @param array<string, string> $publicJwk the public members (RFC 7517 §4)
     * @param string $sealedPrivateJwk a KMS envelope, opaque here
     * @param DateTimeImmutable $activationDueAt when the staging window ends: the timed step activates the key from then on
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

    /**
     * Only a staged key can: the JWK Set has been serving its "kid" since.
     */
    public function activate(DateTimeImmutable $now): void
    {
        if ($this->status !== KeyStatus::Staged) {
            throw new LogicException(sprintf('Only a staged key can be activated; this one is %s.', $this->status->value));
        }
        $this->status = KeyStatus::Active;
        $this->activatedAt = $now;
    }

    /**
     * A new key took over; this one stays published until the retirement date.
     */
    public function startRetiring(DateTimeImmutable $now, DateTimeImmutable $retirementDueAt): void
    {
        if ($this->status !== KeyStatus::Active) {
            throw new LogicException(sprintf('Only an active key can start retiring; this one is %s.', $this->status->value));
        }
        if ($retirementDueAt < $now) {
            throw new InvalidArgumentException('The retirement of a key is due when it starts retiring at the earliest.');
        }
        $this->status = KeyStatus::Retiring;
        $this->retiringAt = $now;
        $this->retirementDueAt = $retirementDueAt;
    }

    /**
     * Out of the JWK Set; due (isDue()) or forced is the caller's decision.
     */
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

    public function isPublished(): bool
    {
        return $this->status->isPublished();
    }

    /**
     * Staged and past its activation date.
     */
    public function isDueForActivation(DateTimeImmutable $now): bool
    {
        return $this->status === KeyStatus::Staged && $this->activationDueAt <= $now;
    }

    /**
     * Retiring and past its retirement date.
     */
    public function isDue(DateTimeImmutable $now): bool
    {
        return $this->status === KeyStatus::Retiring && $this->retirementDueAt !== null && $this->retirementDueAt <= $now;
    }
}
