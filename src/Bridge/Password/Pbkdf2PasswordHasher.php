<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Password;

use function base64_decode;
use Beffroi\Core\Bridge\Password\Algorithm\Pbkdf2AlgorithmInterface;
use Beffroi\Core\Domain\Credential\PasswordAlgorithmInterface;
use Beffroi\Core\Domain\Credential\PasswordHash;
use Beffroi\Core\Exception\LogicException;
use Beffroi\Core\Port\AlgorithmPasswordHasherInterface;
use Beffroi\Core\Port\PasswordHasherInterface;
use function hash_equals;
use function hash_pbkdf2;
use function is_int;
use function is_string;
use SensitiveParameter;
use function sprintf;
use function strlen;

/**
 * RFC 8018 §5.2
 *
 * Verifies the PBKDF2 hashes an import brings, as Keycloak stores them: the derived key in base64, its salt in base64
 * and its iterations beside it. It makes none, so every one of them is replaced at the first successful sign-in.
 */
final readonly class Pbkdf2PasswordHasher implements AlgorithmPasswordHasherInterface
{
    public const string SALT = 'salt';

    public const string ITERATIONS = 'iterations';

    public function __construct(
        private PasswordHasherInterface $today,
    ) {
    }

    public function supports(PasswordAlgorithmInterface $algorithm): bool
    {
        return $algorithm instanceof Pbkdf2AlgorithmInterface;
    }

    public function hash(#[SensitiveParameter] string $plainPassword): PasswordHash
    {
        return $this->today->hash($plainPassword);
    }

    /**
     * A hash whose key, salt or iterations cannot be read verifies no password.
     */
    public function verify(PasswordHash $hash, #[SensitiveParameter] string $plainPassword): bool
    {
        $algorithm = $this->pbkdf2Of($hash);
        $key = base64_decode($hash->encoded, true);
        $salt = $hash->parameters[self::SALT] ?? null;
        $salt = is_string($salt) ? base64_decode($salt, true) : false;
        $iterations = $hash->parameters[self::ITERATIONS] ?? null;
        if ($key === false || $key === '' || $salt === false || ! is_int($iterations) || $iterations < 1) {
            return false;
        }

        return hash_equals($key, hash_pbkdf2($algorithm->digest(), $plainPassword, $salt, $iterations, strlen($key), true));
    }

    public function needsRehash(PasswordHash $hash): bool
    {
        $this->pbkdf2Of($hash);

        return true;
    }

    /**
     * @throws LogicException
     */
    private function pbkdf2Of(PasswordHash $hash): Pbkdf2AlgorithmInterface
    {
        $algorithm = $hash->algorithm;
        if (! $algorithm instanceof Pbkdf2AlgorithmInterface) {
            throw new LogicException(sprintf('The PBKDF2 hasher does not handle "%s" hashes; route them through the registry.', $algorithm->name()));
        }

        return $algorithm;
    }
}
