<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Session\GeoLocation;

/**
 * An implementation never sends the address anywhere and never throws: what it cannot locate has no place.
 */
interface GeoLocatorInterface
{
    public function locate(string $ipAddress): ?GeoLocation;
}
