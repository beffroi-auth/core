<?php

declare(strict_types=1);

namespace Beffroi\Core\Port;

use Beffroi\Core\Domain\Identity\Subject;
use Beffroi\Core\Exception\RuntimeException;
use Symfony\Component\Uid\Uuid;

/**
 * Read where the store keeps it in the clear, so that the answer asks nothing of the KMS: a revocation is
 * announced whatever the KMS answers.
 */
interface SubjectsOfSessionsInterface
{
    /**
     * @throws RuntimeException
     */
    public function of(Uuid $session): Subject;
}
