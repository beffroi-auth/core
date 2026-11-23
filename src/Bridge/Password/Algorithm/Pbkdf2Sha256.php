<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Password\Algorithm;

/**
 * RFC 8018 §5.2
 */
final readonly class Pbkdf2Sha256 implements Pbkdf2AlgorithmInterface
{
    public const string NAME = 'pbkdf2-sha256';

    public function name(): string
    {
        return self::NAME;
    }

    public function digest(): string
    {
        return 'sha256';
    }
}
