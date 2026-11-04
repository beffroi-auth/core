<?php

declare(strict_types=1);

namespace Beffroi\Core\Bridge\JwtLibrary;

use Beffroi\Core\Domain\Key\RotationSchedule;

final readonly class JoseKeyFamily
{
    public function __construct(
        public JoseAlgorithmRegistryInterface $algorithms,
        public RotationSchedule $schedule,
    ) {
    }
}
