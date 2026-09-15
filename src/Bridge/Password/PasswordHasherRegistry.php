<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\Password;

use Heidi\Core\Domain\Credential\PasswordAlgorithm;
use Heidi\Core\Domain\Credential\PasswordHash;
use Heidi\Core\Exception\InvalidArgumentException;
use Heidi\Core\Exception\LogicException;
use Heidi\Core\Port\PasswordHasherInterface;
use SensitiveParameter;
use function sprintf;

/**
 * Routes each hash to the hasher that understands its algorithm, and produces new hashes with the default
 * one. Imported hashes (Keycloak pbkdf2, for instance) get a hasher of their own here and are re-hashed
 * with the default after their first successful verification.
 */
final readonly class PasswordHasherRegistry implements PasswordHasherInterface
{
    /**
     * @var list<PasswordHasherInterface>
     */
    private array $hashers;

    private PasswordHasherInterface $default;

    /**
     * @param iterable<PasswordHasherInterface> $hashers
     */
    public function __construct(iterable $hashers, PasswordAlgorithm $defaultAlgorithm = PasswordAlgorithm::Argon2id)
    {
        $list = [];
        foreach ($hashers as $hasher) {
            $list[] = $hasher;
        }
        if ($list === []) {
            throw new InvalidArgumentException('The password hasher registry needs at least one hasher.');
        }
        $this->hashers = $list;
        $this->default = $this->hasherFor($defaultAlgorithm);
    }

    public function supports(PasswordAlgorithm $algorithm): bool
    {
        return array_any($this->hashers, fn ($hasher) => $hasher->supports($algorithm));
    }

    public function hash(#[SensitiveParameter] string $plainPassword): PasswordHash
    {
        return $this->default->hash($plainPassword);
    }

    public function verify(PasswordHash $hash, #[SensitiveParameter] string $plainPassword): bool
    {
        return $this->hasherFor($hash->algorithm)
            ->verify($hash, $plainPassword);
    }

    /**
     * A hash the default hasher would not have produced needs a rehash, whatever its own hasher thinks.
     */
    public function needsRehash(PasswordHash $hash): bool
    {
        $hasher = $this->hasherFor($hash->algorithm);

        return $hasher !== $this->default || $hasher->needsRehash($hash);
    }

    private function hasherFor(PasswordAlgorithm $algorithm): PasswordHasherInterface
    {
        foreach ($this->hashers as $hasher) {
            if ($hasher->supports($algorithm)) {
                return $hasher;
            }
        }

        throw new LogicException(sprintf('No password hasher handles the "%s" algorithm.', $algorithm->value));
    }
}
