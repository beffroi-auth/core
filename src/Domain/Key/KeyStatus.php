<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Key;

/**
 * The life of a signing key (architecture §3.3, OIDC Core §10.1.1): published before it signs, published
 * after it stopped while a JWS it signed can be alive, then out of the JWK Set.
 */
enum KeyStatus: string
{
    case Staged = 'staged';
    case Active = 'active';
    case Retiring = 'retiring';
    case Retired = 'retired';

    public function isPublished(): bool
    {
        return $this !== self::Retired;
    }
}
