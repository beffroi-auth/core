<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Password\Algorithm;

use Beffroi\Core\Domain\Credential\PasswordAlgorithmInterface;

/**
 * RFC 8018 §5.2
 */
final readonly class Pbkdf2Sha256 implements PasswordAlgorithmInterface
{
    public const string NAME = 'pbkdf2-sha256';

    public function name(): string
    {
        return self::NAME;
    }
}
