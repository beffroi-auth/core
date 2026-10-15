<?php

declare(strict_types=1);

namespace Beffroi\Core\Domain\Key;

use DateInterval;

/**
 * The two figures a family of keys rotates by (architecture §3.3, issue #738): how long a staged key is
 * published before it is used, and how long a key works before the planning stages a successor for it.
 *
 * They belong to a family and not to the tenant, because what a key must wait for is who reads it: a
 * relying party refetches the JWK Set within minutes, a SAML service provider reads the metadata of its
 * identity provider once a day at best, and one that holds a certificate an administrator uploaded reads
 * nothing at all. One figure for every key either cuts the slow readers off at each rotation or holds the
 * fast ones back for days; a family declares its own, and a key reads the figures of its family.
 */
final readonly class RotationSchedule
{
    /**
     * @param DateInterval $stagingDelay how long a staged key of the family is published before it is used
     * @param DateInterval $age how long a key of the family works before a successor is staged for it, counted from its activation
     */
    public function __construct(
        public DateInterval $stagingDelay,
        public DateInterval $age,
    ) {
    }
}
