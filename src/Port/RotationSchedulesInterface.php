<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Key\KeyAlgorithmInterface;
use Beffroi\Core\Domain\Key\RotationSchedule;
use Beffroi\Core\Exception\LogicException;

interface RotationSchedulesInterface
{
    /**
     * @throws LogicException when no family holds the algorithm, which no default may hide
     */
    public function of(KeyAlgorithmInterface $algorithm): RotationSchedule;
}
