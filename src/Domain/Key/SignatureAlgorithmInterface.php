<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Key;

/**
 * RFC 7518 §3
 *
 * Never an HMAC (RFC 8725 §3.1) nor "none".
 */
interface SignatureAlgorithmInterface extends PublishedKeyAlgorithmInterface
{
    /**
     * @return non-empty-string as hash() names it
     */
    public function digestAlgorithm(): string;
}
