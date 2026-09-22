<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\Password;

use Heidi\Core\Domain\Credential\PasswordAlgorithm;
use Heidi\Core\Domain\Credential\PasswordHash;
use Heidi\Core\Exception\InvalidArgumentException;
use Heidi\Core\Exception\LogicException;
use Heidi\Core\Port\AlgorithmPasswordHasherInterface;
use function in_array;
use const PASSWORD_ARGON2_DEFAULT_MEMORY_COST;
use const PASSWORD_ARGON2_DEFAULT_THREADS;
use const PASSWORD_ARGON2_DEFAULT_TIME_COST;
use const PASSWORD_ARGON2ID;
use function password_hash;
use function password_needs_rehash;
use function password_verify;
use SensitiveParameter;
use function sprintf;

/**
 * password_hash() and password_verify(): argon2id for new hashes, argon2i and bcrypt verified then re-hashed.
 */
final readonly class NativePasswordHasher implements AlgorithmPasswordHasherInterface
{
    private const array SUPPORTED = [PasswordAlgorithm::Argon2id, PasswordAlgorithm::Argon2i, PasswordAlgorithm::Bcrypt];

    /**
     * The defaults of PHP meet RFC 9106 §4; a deployment raises them, never lowers them.
     */
    public function __construct(
        private int $memoryCost = PASSWORD_ARGON2_DEFAULT_MEMORY_COST,
        private int $timeCost = PASSWORD_ARGON2_DEFAULT_TIME_COST,
        private int $threads = PASSWORD_ARGON2_DEFAULT_THREADS,
    ) {
        if ($memoryCost < PASSWORD_ARGON2_DEFAULT_MEMORY_COST || $timeCost < PASSWORD_ARGON2_DEFAULT_TIME_COST || $threads < 1) {
            throw new InvalidArgumentException('argon2id parameters cannot go below the defaults of PHP.');
        }
    }

    public function supports(PasswordAlgorithm $algorithm): bool
    {
        return in_array($algorithm, self::SUPPORTED, true);
    }

    public function hash(#[SensitiveParameter] string $plainPassword): PasswordHash
    {
        return new PasswordHash(PasswordAlgorithm::Argon2id, password_hash($plainPassword, PASSWORD_ARGON2ID, $this->options()));
    }

    public function verify(PasswordHash $hash, #[SensitiveParameter] string $plainPassword): bool
    {
        $this->assertSupported($hash->algorithm);

        return password_verify($plainPassword, $hash->encoded);
    }

    public function needsRehash(PasswordHash $hash): bool
    {
        $this->assertSupported($hash->algorithm);

        return $hash->algorithm !== PasswordAlgorithm::Argon2id || password_needs_rehash($hash->encoded, PASSWORD_ARGON2ID, $this->options());
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

    private function assertSupported(PasswordAlgorithm $algorithm): void
    {
        if (! $this->supports($algorithm)) {
            throw new LogicException(sprintf('The native hasher does not handle "%s" hashes; route them through the registry.', $algorithm->value));
        }
    }
}
