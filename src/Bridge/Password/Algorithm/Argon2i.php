<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\Password\Algorithm;

use Heidi\Core\Domain\Credential\PasswordAlgorithmInterface;

/**
 * Argon2i (RFC 9106 §4), verified and re-hashed: what a provider that chose the side-channel-resistant variant left behind.
 */
final readonly class Argon2i implements PasswordAlgorithmInterface
{
    public const string NAME = 'argon2i';

    public function name(): string
    {
        return self::NAME;
    }
}
