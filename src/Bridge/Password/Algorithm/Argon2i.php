<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Password\Algorithm;

use Beffroi\Core\Domain\Credential\PasswordAlgorithmInterface;

/**
 * RFC 9106 §4
 */
final readonly class Argon2i implements PasswordAlgorithmInterface
{
    public const string NAME = 'argon2i';

    public function name(): string
    {
        return self::NAME;
    }
}
