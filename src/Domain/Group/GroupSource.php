<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Group;

enum GroupSource: string
{
    case Local = 'local';
    case UpstreamClaims = 'upstream_claims';
    case Scim = 'scim';

    public function isManaged(): bool
    {
        return $this !== self::Local;
    }
}
