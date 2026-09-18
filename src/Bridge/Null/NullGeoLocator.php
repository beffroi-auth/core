<?php

declare(strict_types=1);

namespace Heidi\Core\Bridge\Null;

use Heidi\Core\Domain\Session\GeoLocation;
use Heidi\Core\Port\GeoLocatorInterface;

/**
 * No database, no location.
 */
final class NullGeoLocator implements GeoLocatorInterface
{
    public function locate(string $ipAddress): ?GeoLocation
    {
        return null;
    }
}
