<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\Password;

use Heidi\Core\Domain\Credential\PasswordAlgorithmInterface;
use Heidi\Core\Domain\Credential\PasswordHash;
use Heidi\Core\Exception\InvalidArgumentException;
use Heidi\Core\Exception\LogicException;
use Heidi\Core\Port\AlgorithmPasswordHasherInterface;
use Heidi\Core\Port\PasswordAlgorithmsInterface;
use Heidi\Core\Port\PasswordHasherInterface;
use SensitiveParameter;
use function sprintf;

/**
 * Routes each hash to the hasher of its algorithm; new hashes use the default one. The port the use cases
 * see, over the hashers that name their algorithms.
 */
final readonly class PasswordHasherRegistry implements PasswordHasherInterface
{
    /**
     * @var list<AlgorithmPasswordHasherInterface>
     */
    private array $hashers;

    private AlgorithmPasswordHasherInterface $default;

    /**
     * @param iterable<AlgorithmPasswordHasherInterface> $hashers
     * @param PasswordAlgorithmsInterface $algorithms the wiring, whose first algorithm is what a new hash is made with
     */
    public function __construct(iterable $hashers, PasswordAlgorithmsInterface $algorithms)
    {
        $list = [];
        foreach ($hashers as $hasher) {
            $list[] = $hasher;
        }
        if ($list === []) {
            throw new InvalidArgumentException('The password hasher registry needs at least one hasher.');
        }
        $this->hashers = $list;
        $this->default = $this->hasherFor($algorithms->defaultAlgorithm());
    }

    public function hash(#[SensitiveParameter] string $plainPassword): PasswordHash
    {
        return $this->default->hash($plainPassword);
    }

    public function verify(PasswordHash $hash, #[SensitiveParameter] string $plainPassword): bool
    {
        return $this->hasherFor($hash->algorithm)->verify($hash, $plainPassword);
    }

    /**
     * A hash of another algorithm than the default needs a rehash.
     */
    public function needsRehash(PasswordHash $hash): bool
    {
        $hasher = $this->hasherFor($hash->algorithm);

        return $hasher !== $this->default || $hasher->needsRehash($hash);
    }

    private function hasherFor(PasswordAlgorithmInterface $algorithm): AlgorithmPasswordHasherInterface
    {
        foreach ($this->hashers as $hasher) {
            if ($hasher->supports($algorithm)) {
                return $hasher;
            }
        }

        throw new LogicException(sprintf('No password hasher handles the "%s" algorithm.', $algorithm->name()));
    }
}
