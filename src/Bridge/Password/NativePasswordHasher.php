<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Password;

use Beffroi\Core\Bridge\Password\Algorithm\Argon2i;
use Beffroi\Core\Bridge\Password\Algorithm\Argon2id;
use Beffroi\Core\Bridge\Password\Algorithm\Bcrypt;
use Beffroi\Core\Domain\Credential\PasswordAlgorithmInterface;
use Beffroi\Core\Domain\Credential\PasswordHash;
use Beffroi\Core\Exception\InvalidArgumentException;
use Beffroi\Core\Exception\LogicException;
use Beffroi\Core\Port\AlgorithmPasswordHasherInterface;
use function in_array;
use const PASSWORD_ARGON2_DEFAULT_MEMORY_COST;
use const PASSWORD_ARGON2_DEFAULT_TIME_COST;
use const PASSWORD_ARGON2ID;
use function password_hash;
use function password_needs_rehash;
use function password_verify;
use SensitiveParameter;
use function sprintf;

final readonly class NativePasswordHasher implements AlgorithmPasswordHasherInterface
{
    private const array SUPPORTED = [Argon2id::NAME, Argon2i::NAME, Bcrypt::NAME];

    /**
     * RFC 9106 §4
     *
     * The memory-constrained set is 64 MiB, three passes and four lanes. PHP's defaults hold that memory and spend
     * a pass more; the lanes, the "threads" of password_hash(), divide the memory instead of adding to it.
     */
    public const int MEMORY_COST = PASSWORD_ARGON2_DEFAULT_MEMORY_COST;

    public const int TIME_COST = PASSWORD_ARGON2_DEFAULT_TIME_COST;

    public const int THREADS = 4;

    public function __construct(
        private int $memoryCost = self::MEMORY_COST,
        private int $timeCost = self::TIME_COST,
        private int $threads = self::THREADS,
    ) {
        if ($memoryCost < self::MEMORY_COST || $timeCost < self::TIME_COST || $threads < self::THREADS) {
            throw new InvalidArgumentException(sprintf(
                'argon2id parameters cannot go below %d KiB of memory, %d passes and %d lanes.',
                self::MEMORY_COST,
                self::TIME_COST,
                self::THREADS,
            ));
        }
    }

    public function supports(PasswordAlgorithmInterface $algorithm): bool
    {
        return in_array($algorithm->name(), self::SUPPORTED, true);
    }

    public function hash(#[SensitiveParameter] string $plainPassword): PasswordHash
    {
        return new PasswordHash(new Argon2id(), password_hash($plainPassword, PASSWORD_ARGON2ID, $this->options()));
    }

    public function verify(PasswordHash $hash, #[SensitiveParameter] string $plainPassword): bool
    {
        $this->assertSupported($hash->algorithm);

        return password_verify($plainPassword, $hash->encoded);
    }

    public function needsRehash(PasswordHash $hash): bool
    {
        $this->assertSupported($hash->algorithm);

        return $hash->algorithm->name() !== Argon2id::NAME || password_needs_rehash($hash->encoded, PASSWORD_ARGON2ID, $this->options());
    }

    /**
     * @return array{memory_cost: int, time_cost: int, threads: int}
     */
    private function options(): array
    {
        return [
            'memory_cost' => $this->memoryCost,
            'time_cost' => $this->timeCost,
            'threads' => $this->threads,
        ];
    }

    private function assertSupported(PasswordAlgorithmInterface $algorithm): void
    {
        if (! $this->supports($algorithm)) {
            throw new LogicException(sprintf('The native hasher does not handle "%s" hashes; route them through the registry.', $algorithm->name()));
        }
    }
}
