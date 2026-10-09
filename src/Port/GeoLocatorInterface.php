<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Session\GeoLocation;

/**
 * Turns an IP address into an approximate location. Called asynchronously, never on the request path.
 *
 * **It answers the place of an address and nothing else** (issue #473): it is given one address, it gives
 * back what a database says of it, and no implementation of it sends that address anywhere. A lookup over
 * a service of somebody else would hand this provider's addresses to that somebody, which is the form of
 * this that decision 0006 does not leave as an option.
 *
 * **It never throws.** An address nothing knows, a database that was never downloaded and a database that
 * cannot be read are all one answer, no place, because the caller runs on a lane behind a login that has
 * already happened: an enrichment that failed loses a place, and nothing else.
 */
interface GeoLocatorInterface
{
    public function locate(string $ipAddress): ?GeoLocation;
}
