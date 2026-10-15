<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Key\KeyAlgorithmInterface;
use Beffroi\Core\Domain\Key\RotationSchedule;
use Beffroi\Core\Exception\LogicException;

/**
 * The figures each family of keys rotates by (issue #738): what the staging and the succession of a key
 * read for the key they handle, so that neither of them holds a figure of its own and no family is
 * enumerated anywhere. A family is what the wiring makes of it: the algorithms it holds, and the schedule
 * it declares beside them.
 */
interface RotationSchedulesInterface
{
    /**
     * The schedule of the family that holds the algorithm.
     *
     * @throws LogicException when no family holds it: a wiring that forgot one, which no default may hide
     */
    public function of(KeyAlgorithmInterface $algorithm): RotationSchedule;
}
