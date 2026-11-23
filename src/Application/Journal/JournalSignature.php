<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Journal;

use Beffroi\Core\Domain\Journal\Checkpoint;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\BrokenJournalException;
use Beffroi\Core\Exception\KeyNotFoundException;
use Beffroi\Core\Exception\KmsUnavailableException;
use Beffroi\Core\Exception\RuntimeException;
use Beffroi\Core\Port\AtomicWritesInterface;
use Beffroi\Core\Port\ChainOfActsInterface;
use Psr\Clock\ClockInterface;

/**
 * Only a chain that continues the last signature is signed: what was rewritten before it stays visible.
 */
final readonly class JournalSignature
{
    public function __construct(
        private ChainOfActsInterface $chain,
        private ChainWalk $walk,
        private JournalCheckpoints $checkpoints,
        private AtomicWritesInterface $writes,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws BrokenJournalException
     * @throws KeyNotFoundException
     * @throws KmsUnavailableException
     * @throws RuntimeException when the signature cannot be kept
     */
    public function sign(Tenant $tenant): Checkpoint
    {
        return $this->writes->atomically(function () use ($tenant): Checkpoint {
            $this->chain->lockedHead($tenant);
            $last = $this->checkpoints->last($tenant);
            $keptAfter = $this->walk->start($tenant, $last);
            $checkpoint = new Checkpoint($tenant->id, $this->walk->end($tenant, $last->head ?? $keptAfter), $keptAfter, $this->clock->now());
            $this->checkpoints->sign($tenant, $checkpoint);

            return $checkpoint;
        });
    }
}
