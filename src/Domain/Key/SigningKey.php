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
 */
final class SigningKey
{
    public private(set) KeyStatus $status = KeyStatus::Active;

    public private(set) ?DateTimeImmutable $retiringAt = null;

    public private(set) ?DateTimeImmutable $retiredAt = null;

    /**
     * @param array<string, string> $publicJwk the public members (RFC 7517 §4), including kid, kty, alg and use
     * @param string $sealedPrivateJwk the private JWK as a KMS envelope, opaque here
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
     * A new key took over: this one keeps verifying what it signed until the last such token expires.
     */
    public function startRetiring(DateTimeImmutable $now): void
    {
        if ($this->status !== KeyStatus::Active) {
            throw new LogicException(sprintf('Only an active key can start retiring; this one is %s.', $this->status->value));
        }
        $this->status = KeyStatus::Retiring;
        $this->retiringAt = $now;
    }

    /**
     * Out of the JWK Set. Only once every token it signed has expired.
     */
    public function retire(DateTimeImmutable $now): void
    {
        if ($this->status !== KeyStatus::Retiring) {
            throw new LogicException(sprintf('Only a retiring key can be retired; this one is %s.', $this->status->value));
        }
        $this->status = KeyStatus::Retired;
        $this->retiredAt = $now;
    }

    public function isActive(): bool
    {
        return $this->status === KeyStatus::Active;
    }

    public function isPublished(): bool
    {
        return $this->status->isPublished();
    }
}
