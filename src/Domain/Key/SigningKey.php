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
 * A signing key of a tenant, as persisted: the public JWK in clear, the private JWK sealed by the KMS
 * (never stored in clear, decrypted only in the memory of the process that signs). The "kid" is the
 * RFC 7638 thumbprint of the public key.
 *
 * A new key is staged: published and signing nothing. It goes active, then retiring when another takes
 * over, then retired once its retirement date has passed (KeyStatus). Every date is a fact of the key,
 * written when the transition happens; the retirement date is computed when the key starts retiring, from
 * the longest lifetime of a JWS the tenant issues, so that retiring it is a matter of time and not of
 * judgement.
 */
final class SigningKey
{
    public private(set) KeyStatus $status = KeyStatus::Staged;

    public private(set) ?DateTimeImmutable $activatedAt = null;

    public private(set) ?DateTimeImmutable $retiringAt = null;

    /**
     * When the retirement is due: the last JWS this key could have signed has expired by then.
     */
    public private(set) ?DateTimeImmutable $retirementDueAt = null;

    public private(set) ?DateTimeImmutable $retiredAt = null;

    /**
     * @param array<string, string> $publicJwk the public members (RFC 7517 §4), including kid, kty, alg and use
     * @param string $sealedPrivateJwk the private JWK as a KMS envelope, opaque here
     * @param DateTimeImmutable $createdAt when the key was generated and staged
     */
    public function __construct(
        public readonly string $kid,
        public readonly Tenant $tenant,
        public readonly SignatureAlgorithm $algorithm,
        public readonly array $publicJwk,
        #[SensitiveParameter]
        public readonly string $sealedPrivateJwk,
        public readonly DateTimeImmutable $createdAt,
    ) {
        if ($kid === '') {
            throw new InvalidArgumentException('A signing key has a key identifier.');
        }
        if ($sealedPrivateJwk === '') {
            throw new InvalidArgumentException('A signing key carries its sealed private material.');
        }
        foreach ([
            'kid' => $kid,
            'kty' => $algorithm->keyType(),
            'alg' => $algorithm->value,
            'use' => 'sig',
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
     * The key signs from now on. Only a staged key can: the JWK Set has been serving its "kid" since it was
     * staged, which is what lets a relying party with a warm cache verify what it signs.
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
     * A new key took over: this one keeps verifying what it signed until the last such JWS expires, which
     * is the retirement date given.
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
     * Out of the JWK Set. The one who calls this decides whether the retirement is due (isDue()) or forced.
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
     * Retiring, and past its retirement date: nothing it signed is alive any more.
     */
    public function isDue(DateTimeImmutable $now): bool
    {
        return $this->status === KeyStatus::Retiring && $this->retirementDueAt !== null && $this->retirementDueAt <= $now;
    }
}
