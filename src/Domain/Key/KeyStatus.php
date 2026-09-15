<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Key;

/**
 * The life of a signing key: it signs while active, it is still published while retiring (tokens signed with
 * it are still in circulation), it disappears from the JWK Set once retired. One active key per tenant and
 * algorithm; the rotation window is the time a key spends retiring.
 */
enum KeyStatus: string
{
    case Active = 'active';
    case Retiring = 'retiring';
    case Retired = 'retired';

    public function isPublished(): bool
    {
        return $this !== self::Retired;
    }
}
