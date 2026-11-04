<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Key;

/**
 * OIDC Core §10.1.1
 *
 * A status says nothing of publication: a key the tenant keeps is never published, whatever its status.
 */
enum KeyStatus: string
{
    case Staged = 'staged';
    case Active = 'active';
    case Retiring = 'retiring';
    case Retired = 'retired';

    public function isInService(): bool
    {
        return $this !== self::Retired;
    }
}
