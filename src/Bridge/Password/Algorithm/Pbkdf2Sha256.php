<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Password\Algorithm;

use Beffroi\Core\Domain\Credential\PasswordAlgorithmInterface;

/**
 * PBKDF2 over HMAC-SHA-256 (RFC 8018 §5.2), a name an import may bring. The provider reads such a hash back and names it; whether it can verify one is up to the hashers wired beside it.
 */
final readonly class Pbkdf2Sha256 implements PasswordAlgorithmInterface
{
    public const string NAME = 'pbkdf2-sha256';

    public function name(): string
    {
        return self::NAME;
    }
}
