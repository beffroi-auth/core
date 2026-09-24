<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\Password\Algorithm;

use Heidi\Core\Domain\Credential\PasswordAlgorithmInterface;

/**
 * Argon2id (RFC 9106 §4), what this provider makes a new hash with: the memory-hard function the RFC names for password hashing when the side channels of Argon2i and the tradeoffs of Argon2d are both a concern.
 */
final readonly class Argon2id implements PasswordAlgorithmInterface
{
    public const string NAME = 'argon2id';

    public function name(): string
    {
        return self::NAME;
    }
}
