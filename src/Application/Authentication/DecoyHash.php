<?php

declare(strict_types=1);

namespace Heidi\Core\Application\Authentication;

use Heidi\Core\Domain\Credential\PasswordHash;
use Heidi\Core\Port\PasswordHasherInterface;
use SensitiveParameter;
use Symfony\Component\Uid\Uuid;

/**
 * The hash a refused password is verified against when no hash of the user could be: the user does not
 * exist, holds no password, or has tried too often. What is paid is the time, so that a refusal costs
 * what a wrong password costs and does not say whether the account exists (decision 0006). One place for
 * every login that verifies a password, PasswordAuthenticator and the firewall of the bundle alike. The
 * hash is produced by the hasher of the day, so that it costs what a current hash costs, from a value
 * nobody knows, and once per process: a shared service, never a copy per caller.
 */
final class DecoyHash
{
    private ?PasswordHash $hash = null;

    public function __construct(
        private readonly PasswordHasherInterface $hasher,
    ) {
    }

    /**
     * Verifies the password against the decoy and keeps nothing of the answer, which is always no.
     */
    public function verify(#[SensitiveParameter] string $password): void
    {
        $this->hasher->verify($this->hash ??= $this->hasher->hash(Uuid::v4()->toRfc4122()), $password);
    }
}
