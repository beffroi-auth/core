<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

/**
 * Where the randomness of a drawn value comes from. A use case is given its clock (PSR-20) rather than
 * reading the wall clock and its identifiers rather than drawing them (UuidGeneratorInterface); the bytes
 * a secret is made of are the same ambient input, so they are given too, and every value this provider
 * hands out, an authorization code, a refresh token, a request URI, an opaque access token, a TOTP
 * secret, comes from here.
 *
 * What is drawn stays bytes, as a clock hands back a DateTimeImmutable: what has to be inverted is the
 * source, not the value. An implementation is a cryptographically secure source or it is a test double,
 * and there is no third kind.
 */
interface RandomnessInterface
{
    /**
     * @param positive-int $length
     *
     * @return non-empty-string exactly $length bytes
     */
    public function bytes(int $length): string;
}
