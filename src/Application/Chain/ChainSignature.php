<?php

declare(strict_types=1);

namespace Beffroi\Core\Application\Chain;

use Beffroi\Core\Domain\Chain\ChainInterface;
use Beffroi\Core\Domain\Chain\Checkpoint;
use Beffroi\Core\Domain\Tenant\Tenant;
use Beffroi\Core\Exception\BrokenChainException;
use Beffroi\Core\Exception\KeyNotFoundException;
use Beffroi\Core\Exception\KmsUnavailableException;
use Beffroi\Core\Exception\RuntimeException;
use Beffroi\Core\Port\AtomicWritesInterface;
use Beffroi\Core\Port\ChainOfRowsInterface;
use Psr\Clock\ClockInterface;

/**
 * Only the rows that continue the last signature are signed, while the row it names is still the one it signed: what
 * was rewritten before it stays visible. The first signature walks the whole chain, every later one carries how far
 * and when the whole chain was last walked, and what that walk found broken.
 */
final readonly class ChainSignature
{
    public function __construct(
        private ChainOfRowsInterface $rows,
        private ChainWalk $walk,
        private ChainCheckpoints $checkpoints,
        private AtomicWritesInterface $writes,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws BrokenChainException
     * @throws KeyNotFoundException
     * @throws KmsUnavailableException
     * @throws RuntimeException when the signature cannot be kept
     */
    public function sign(Tenant $tenant, ChainInterface $chain): Checkpoint
    {
        return $this->writes->atomically(function () use ($tenant, $chain): Checkpoint {
            $this->rows->lockedHead($tenant, $chain);
            $last = $this->checkpoints->last($tenant, $chain);
            if ($last !== null) {
                $this->walk->assertTheSignedRowHolds($tenant, $chain, $last);
            }
            $keptAfter = $this->walk->start($tenant, $chain, $last);
            $now = $this->clock->now();
            $head = $this->walk->end($tenant, $chain, $last->head ?? $keptAfter);
            $checkpoint = $last === null
                ? new Checkpoint($tenant->id, $chain->name(), $head, $keptAfter, $head, $now, $now)
                : new Checkpoint($tenant->id, $chain->name(), $head, $keptAfter, $last->walked, $last->walkedAt, $now, $last->broken);
            $this->checkpoints->sign($tenant, $chain, $checkpoint);

            return $checkpoint;
        });
    }
}
