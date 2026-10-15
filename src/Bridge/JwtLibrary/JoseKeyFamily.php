<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

use Beffroi\Core\Domain\Key\RotationSchedule;

/**
 * One family of keys of the tenant, as the wiring declares it (issue #738): the registry of the algorithms
 * of one use, and the schedule its keys rotate by. The registry says what the family holds, the schedule
 * what a key of it waits for and how long it works; a family added to the wiring is one more of these, and
 * nothing reads which family it is.
 */
final readonly class JoseKeyFamily
{
    public function __construct(
        public JoseAlgorithmRegistryInterface $algorithms,
        public RotationSchedule $schedule,
    ) {
    }
}
