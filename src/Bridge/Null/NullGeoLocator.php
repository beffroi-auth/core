<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\Null;

use Beffroi\Core\Domain\Session\GeoLocation;
use Beffroi\Core\Port\GeoLocatorInterface;

final class NullGeoLocator implements GeoLocatorInterface
{
    public function locate(string $ipAddress): ?GeoLocation
    {
        return null;
    }
}
