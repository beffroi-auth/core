<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Password\Algorithm;

use Beffroi\Core\Domain\Credential\PasswordAlgorithmInterface;

/**
 * bcrypt, verified and re-hashed: what most of the providers a deployment imports from have stored for twenty years.
 */
final readonly class Bcrypt implements PasswordAlgorithmInterface
{
    public const string NAME = 'bcrypt';

    public function name(): string
    {
        return self::NAME;
    }
}
