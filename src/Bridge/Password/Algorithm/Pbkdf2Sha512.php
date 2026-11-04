<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Password\Algorithm;

use Beffroi\Core\Domain\Credential\PasswordAlgorithmInterface;

/**
 * RFC 8018 §5.2
 */
final readonly class Pbkdf2Sha512 implements PasswordAlgorithmInterface
{
    public const string NAME = 'pbkdf2-sha512';

    public function name(): string
    {
        return self::NAME;
    }
}
