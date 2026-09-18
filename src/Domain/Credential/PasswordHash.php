<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Credential;

use Heidi\Core\Exception\InvalidArgumentException;
use SensitiveParameter;

/**
 * A password hash with its algorithm; "parameters" carries what an imported format keeps apart from the digest.
 */
final readonly class PasswordHash
{
    /**
     * @param array<string, int|string> $parameters
     */
    public function __construct(
        public PasswordAlgorithm $algorithm,
        #[SensitiveParameter]
        public string $encoded,
        public array $parameters = [],
    ) {
        if ($encoded === '') {
            throw new InvalidArgumentException('A password hash cannot be empty.');
        }
    }
}
