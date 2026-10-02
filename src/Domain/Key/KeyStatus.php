<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Key;

/**
 * The life of a key of a tenant (architecture §3.3, OIDC Core §10.1.1): published before it is used,
 * published after it stopped while what it produced, or what was encrypted to it, can still arrive, then
 * out of the JWK Set.
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
