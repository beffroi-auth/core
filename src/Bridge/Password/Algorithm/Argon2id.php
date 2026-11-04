<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Password\Algorithm;

use Beffroi\Core\Domain\Credential\PasswordAlgorithmInterface;

/**
 * RFC 9106 §4
 */
final readonly class Argon2id implements PasswordAlgorithmInterface
{
    public const string NAME = 'argon2id';

    public function name(): string
    {
        return self::NAME;
    }
}
