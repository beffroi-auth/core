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
 * Routes each hash to the hasher of its algorithm; new hashes use the default one.
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
     * A hash of another algorithm than the default needs a rehash.
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
