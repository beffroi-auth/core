<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use Beffroi\Core\Exception\InvalidArgumentException;
use SensitiveParameter;

/**
 * A password hash with the algorithm it was made with; "parameters" carries what an imported format
 * keeps apart from the digest. The algorithm is the class the wiring holds for that name
 * (PasswordAlgorithmInterface), so a hash read back from a store says which of them it belongs to and
 * never a word nothing accounts for.
 */
final readonly class PasswordHash
{
    /**
     * @param array<string, int|string> $parameters
     */
    public function __construct(
        public PasswordAlgorithmInterface $algorithm,
        #[SensitiveParameter]
        public string $encoded,
        public array $parameters = [],
    ) {
        if ($encoded === '') {
            throw new InvalidArgumentException('A password hash cannot be empty.');
        }
    }
}
