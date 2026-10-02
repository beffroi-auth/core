<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Session\GeoLocation;

/**
 * Turns an IP address into an approximate location. Called asynchronously, never on the request path.
 */
interface GeoLocatorInterface
{
    public function locate(string $ipAddress): ?GeoLocation;
}
