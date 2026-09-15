<?php

declare(strict_types=1);

namespace Heidi\Core\Port;

use Heidi\Core\Domain\Session\GeoLocation;

/**
 * Turns an IP address into an approximate location. Called asynchronously, never on the request path.
 */
interface GeoLocatorInterface
{
    public function locate(string $ipAddress): ?GeoLocation;
}
