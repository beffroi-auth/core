<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Password\Algorithm;

use Beffroi\Core\Domain\Credential\PasswordAlgorithmInterface;

/**
 * RFC 8018 §5.2
 */
interface Pbkdf2AlgorithmInterface extends PasswordAlgorithmInterface
{
    /**
     * The hash of the HMAC that is the pseudorandom function, as hash_hmac_algos() names it.
     *
     * @return non-falsy-string
     */
    public function digest(): string;
}
