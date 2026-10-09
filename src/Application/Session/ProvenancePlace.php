<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Session;

use Beffroi\Core\Domain\Session\GeoLocation;
use Beffroi\Core\Domain\Session\RequestContext;
use Beffroi\Core\Port\GeoLocatorInterface;

/**
 * The place a provenance came from (issue #473), the one reading everything that enriches one goes
 * through: a session and an act of the journal are two rows, and what "from where" means for an address
 * is one answer or they would drift apart.
 *
 * It asks the locator once and only where there is something to ask: a provenance with no address has no
 * place to find, and one that already carries a place was enriched before, a second lookup being a read
 * of a database for an answer already written down.
 */
final readonly class ProvenancePlace
{
    public function __construct(
        private GeoLocatorInterface $locator,
    ) {
    }

    /**
     * The place of that provenance, and the place it already carried when it carries one; nothing when
     * the address is unknown or the database answers nothing, which is the honest answer of a deployment
     * that holds no database at all.
     */
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
