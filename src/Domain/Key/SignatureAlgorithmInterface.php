<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Key;

/**
 * One JWS algorithm the provider signs with (RFC 7518 §3). What the provider signs with and what it
 * announces is what is wired: a new algorithm is a class that implements this interface and a line in the
 * wiring, never a case added to an enumeration. RS256 is mandatory (OIDC Core §15.1), FAPI 2.0 §5.2.2
 * refuses it; no HMAC (RFC 8725 §3.1), no "none".
 */
interface SignatureAlgorithmInterface extends KeyAlgorithmInterface
{
    /**
     * As hash() names it; "at_hash" and "c_hash" take the left half of that digest (OIDC Core §3.1.3.6).
     *
     * @return non-empty-string
     */
    public function digestAlgorithm(): string;
}
