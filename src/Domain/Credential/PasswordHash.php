<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Credential;

use Beffroi\Core\Exception\InvalidArgumentException;
use SensitiveParameter;

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
