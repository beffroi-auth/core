<?php

declare(strict_types=1);

namespace Heidi\Core\Domain\Group;

/**
 * Where a group or a membership comes from; the source wins over a manual edit (decision 0002).
 */
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
