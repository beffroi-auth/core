<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Authentication;

use Beffroi\Core\Domain\Credential\PasswordHash;
use Beffroi\Core\Port\MonotonicClockInterface;
use Beffroi\Core\Port\PasswordHasherInterface;
use Beffroi\Core\Port\UuidGeneratorInterface;
use SensitiveParameter;

/**
 * Makes a refusal cost what a wrong password costs, so that the time does not say whether the account exists.
 */
final class DecoyHash
{
    private ?PasswordHash $hash = null;

    /**
     * Nanoseconds the last verification of the decoy took in this process.
     */
    private ?int $cost = null;

    public function __construct(
        private readonly PasswordHasherInterface $hasher,
        private readonly UuidGeneratorInterface $uuids,
        private readonly MonotonicClockInterface $clock,
    ) {
    }

    public function verify(#[SensitiveParameter] string $password): void
    {
        $hash = $this->hash ??= $this->hasher->hash($this->uuids->opaque()->toRfc4122());
        $start = $this->clock->now();
        $this->hasher->verify($hash, $password);
        $this->cost = $this->clock->now() - $start;
    }

    /**
     * For a hash the hasher would replace, whose algorithm or parameters cost another time than the decoy's
     * (decision 0058).
     */
    public function verifyNoFaster(PasswordHash $hash, #[SensitiveParameter] string $password): bool
    {
        $start = $this->clock->now();
        $verified = $this->hasher->verify($hash, $password);
        if ($this->cost === null) {
            // Nothing measured yet: the decoy itself is paid, which takes at least its own time.
            $this->verify($password);

            return $verified;
        }
        $left = $this->cost - ($this->clock->now() - $start);
        if ($left > 0) {
            $this->clock->waitFor($left);
        }

        return $verified;
    }
}
