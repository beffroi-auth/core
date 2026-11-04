<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Session;

use Beffroi\Core\Domain\Session\GeoLocation;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Port\GeoLocatorInterface;

final readonly class ProvenancePlace
{
    public function __construct(
        private GeoLocatorInterface $locator,
    ) {
    }

    public function of(RequestContext $context): ?GeoLocation
    {
        if ($context->location !== null) {
            return $context->location;
        }
        if ($context->ipAddress === null) {
            return null;
        }

        return $this->locator->locate($context->ipAddress);
    }
}
