<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Authentication;

use Beffroi\Core\Domain\Credential\PasswordHash;
use Beffroi\Core\Port\PasswordHasherInterface;
use Beffroi\Core\Port\UuidGeneratorInterface;
use SensitiveParameter;

/**
 * Makes a refusal cost what a wrong password costs, so that the time does not say whether the account exists.
 */
final class DecoyHash
{
    private ?PasswordHash $hash = null;

    public function __construct(
        private readonly PasswordHasherInterface $hasher,
        private readonly UuidGeneratorInterface $uuids,
    ) {
    }

    public function verify(#[SensitiveParameter] string $password): void
    {
        $this->hasher->verify($this->hash ??= $this->hasher->hash($this->uuids->opaque()->toRfc4122()), $password);
    }
}
